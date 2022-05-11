<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Entity\Payment;
use App\Repository\AppointmentRepository;
use App\Repository\PaymentRepository;
use App\Repository\UserRepository;
use App\Services\MangoPayService;
use App\Services\NotificationsService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use MangoPay\BrowserInfo;
use MangoPay\MangoPayApi;
use MangoPay\PayInPaymentDetailsPreAuthorized;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class PaymentController extends AbstractController
{
    private $em;
    public function __construct(EntityManagerInterface $entityManager, private MailerInterface $mailer, private HttpClientInterface $httpClient)
    {
        $this->em = $entityManager;
    }
    // Create Card Registration
    #[Route('/payment', name: 'app_payment')]
    public function bridgeBeforePayment(MangoPayService $mangoPayService, Request $request, UserRepository $userRepository): Response
    {
        $session = new Session();
        $appointments = $request->query->get("appointId");
        $appointmentsArray = explode(',', trim($appointments, '[]'));
        $appointmentsIdsArray = [];
        foreach ($appointmentsArray as $id)
        {
            $appointmentsIdsArray[] = intval($id);
        }
        $session->set('appointments_ids_array', $appointmentsIdsArray);
        $session->set('buyer_id', (int)$request->query->get("buyer"));
        $session->set('seller_id', (int) $request->query->get("seller"));
        $session->set('amount', floatval($request->query->get("total")));
        $session->set('currency', 'EUR');
        return $this->redirectToRoute('app_payment_form');
    }

    #[Route('/payment-form', name: 'app_payment_form')]
    public function payment(MangoPayService $mangoPayService, Request $request, UserRepository $userRepository): Response
    {
        $session = new Session();
        $amount = $session->get('amount');
        $buyer = $userRepository->find($session->get('buyer_id'));
        $seller = $userRepository->find($session->get('seller_id'));
        $buyerMangoId = $buyer->getMangoUserId();
        $buyerMangoWalletId = $buyer->getMangoWalletId();
        $sellerMangoId = $seller->getMangoUserId();
        $sellerMangoWalletId = $seller->getMangoWalletId();
        //save variables in session
        $session->set('buyer_mango_id', $buyerMangoId);
        $session->set('buyer_mango_wallet_id', $buyerMangoWalletId);
        $session->set('seller_mango_id', $sellerMangoId);
        $session->set('seller_mango_wallet_id', $sellerMangoWalletId);
        //returnUrl
        $returnUrl = 'http' . ( isset($_SERVER['HTTPS']) ? 's' : '' ) . '://' . $_SERVER['HTTP_HOST'];
        $returnUrl .= substr($_SERVER['REQUEST_URI'], 0, strripos($_SERVER['REQUEST_URI'], '/') + 1);
        $returnUrl .= 'payment/create-payment';
        return $this->render('payment/create_pay_in.html.twig', [
            'result' => (array) $mangoPayService->registerCard($buyerMangoId ,'EUR', $amount),
            'returnUrl' => $returnUrl,
            'amount' => $amount
        ]);
    }

    // Create PayIn To wallet
    #[Route('/payment/create-payment', name: 'create_payment')]
    public function createPayIn(MangoPayService $mangoPayService, AppointmentRepository $appointmentRepository, UserRepository $userRepository, NotificationsService $notificationsService)
    {
        $session = new Session();
        $result = $mangoPayService->createPayInForUserWallet($session->get('buyer_mango_id'), $session->get('buyer_mango_wallet_id'));
        if($result->ExecutionDetails->SecureModeNeeded)
        {
            return $this->redirect($result->ExecutionDetails->SecureModeRedirectURL);
        }
        else
        {
            $payInId = $result->Id;
            $buyerMangoId = $session->get('buyer_mango_id');
            $buyerMangoWalletId = $session->get('buyer_mango_wallet_id');
            $sellerMangoId = $session->get('seller_mango_id');
            $sellerMangoWalletId = $session->get('seller_mango_wallet_id');
            $appointmentsIdsArray = $session->get('appointments_ids_array');
            $amount = $session->get('amount');
            $currency = $session->get('currency');
            $cardId = $session->get('card_id');
            $status = "Pending";
            $buyerId = $session->get('buyer_id');
            $sellerId = $session->get('seller_id');
            $paymentObject = new Payment();
            $paymentObject->setCardId($cardId)
                ->setAmount($amount)
                ->setMangoBuyerId($buyerMangoId)
                ->setMangoBuyerWalletId($buyerMangoWalletId)
                ->setMangoSellerId($sellerMangoId)
                ->setMangoSellerWalletId($sellerMangoWalletId)
                ->setPayinId($payInId)
                ->setCurrency($currency)
                ->setBuyerId($buyerId)
                ->setSellerId($sellerId)
                ->setCreatedAt(new \DateTime())
            ;
            if($result->Status !== "SUCCEEDED")
            {
                $status = "Failed";
            }
            $paymentObject->setStatus($status);
            $this->em->persist($paymentObject);
            $this->em->flush();
            foreach ($appointmentsIdsArray as $id)
            {
                $appointment = $appointmentRepository->find($id);
                if($appointment)
                {
                    $appointment->setAssociatedPayment($paymentObject);
                    $this->em->persist($appointment);
                }
            }
            $this->em->flush();
            if($result->Status !== "SUCCEEDED")
            {
                foreach ($appointmentsIdsArray as $id)
                {
                    $appointment = $appointmentRepository->find($id);
                    if($appointment)
                    {
                        $appointment->setAssociatedPayment($paymentObject);
                        $appointment->setStatus("Declined");
                        $this->em->persist($appointment);
                    }
                }
                $this->em->flush();
                return $this->redirectToRoute('payment_failed');
            }
            $buyer = $userRepository->findBy(["mangoUserId" => $buyerMangoId])[0];
            $seller = $userRepository->findBy(["mangoUserId" => $sellerMangoId])[0];
            $notificationsService->sendAndSaveNotifications($buyer, 'Vous venez de réserver un RDV!');
            $notificationsService->sendAndSaveNotifications($seller, 'Vous avez un RDV à valider!');
            $buyerEmail = $buyer->getEmail();
            $sellerEmail = $seller->getEmail();
            $this->processSendingSuccessPaymentEmail($paymentObject, $this->mailer, $userRepository);
            $this->processSendingSuccessAppointmentEmail("seller_payment_done", $sellerEmail, $this->mailer);
            return $this->redirectToRoute('payment_success', [
                'payinId' => $result->Id
            ]);
        }
    }

    // Payment Successful
    #[Route('/payment/check', name: 'payment_check')]
    public function paymentCheck(Request $request, MangoPayService $mangoPayService, AppointmentRepository $appointmentRepository, UserRepository $userRepository)
    {
        $session = new Session();
        $payInId = $request->query->get("transactionId");
        $result = $mangoPayService->checkPayInStatus($payInId);
        $payInId = $result->Id;
        $buyerMangoId = $session->get('buyer_mango_id');
        $buyerMangoWalletId = $session->get('buyer_mango_wallet_id');
        $sellerMangoId = $session->get('seller_mango_id');
        $sellerMangoWalletId = $session->get('seller_mango_wallet_id');
        $appointmentsIdsArray = $session->get('appointments_ids_array');
        $amount = $session->get('amount');
        $currency = $session->get('currency');
        $cardId = $session->get('card_id');
        $status = "Pending";
        $buyerId = $session->get('buyer_id');
        $sellerId = $session->get('seller_id');
        $buyer = $userRepository->findBy(["mangoUserId" => $buyerMangoId]);
        $seller = $userRepository->findBy(["mangoUserId" => $sellerMangoId]);
        $buyerEmail = $buyer->getEmail();
        $sellerEmail = $buyer->getEmail();
        $paymentObject = new Payment();
        $paymentObject->setCardId($cardId)
            ->setAmount($amount)
            ->setMangoBuyerId($buyerMangoId)
            ->setMangoBuyerWalletId($buyerMangoWalletId)
            ->setMangoSellerId($sellerMangoId)
            ->setMangoSellerWalletId($sellerMangoWalletId)
            ->setPayinId($payInId)
            ->setCurrency($currency)
            ->setBuyerId($buyerId)
            ->setSellerId($sellerId)
            ->setCreatedAt(new \DateTime())
        ;
        if($result->Status !== "SUCCEEDED")
        {
            $status = "Failed";
        }
        $paymentObject->setStatus($status);
        $this->em->persist($paymentObject);
        $this->em->flush();
        foreach ($appointmentsIdsArray as $id)
        {
            $appointment = $appointmentRepository->find($id);
            if($appointment)
            {
                $appointment->setAssociatedPayment($paymentObject);
                $this->em->persist($appointment);
            }
        }
        $this->em->flush();
        if($result->Status !== "SUCCEEDED")
        {
            foreach ($appointmentsIdsArray as $id)
            {
                $appointment = $appointmentRepository->find($id);
                if($appointment)
                {
                    $appointment->setAssociatedPayment($paymentObject);
                    $appointment->setStatus("Declined");
                    $this->em->persist($appointment);
                }
            }
            $this->em->flush();
            return $this->redirectToRoute('payment_failed');
        }

        return $this->redirectToRoute('payment_success', [
            'payinId' => $result->Id
        ]);

    }

    #[Route('/payment/{payinId}/success', name: 'payment_success')]
    public function paymentSuccess(Request $request, MangoPayService $mangoPayService, $payinId)
    {
        return $this->render('payment/payment_success.html.twig',[
            'payin_id' => $payinId
        ]);
    }
    // Payment Failed
    #[Route('/payment/failed', name: 'payment_failed')]
    public function paymentFail()
    {
        return $this->render('payment/payment_fail.html.twig');
    }

    // Create Transfer from Buyer Wallet to Seller Wallet
    #[Route('/api/payment/create-transfer', name: 'create_transfer')]
    public function createTransfer(MangoPayService $mangoPayService, Request $request, PaymentRepository $paymentRepository, UserRepository $userRepository, NotificationsService $notificationsService): Response
    {
        $paymentId = (int) $request->query->get("paymentId");
        $selectedPayment = $paymentRepository->find($paymentId);
        $amount = floatval($selectedPayment->getAmount() * 100);
        $buyerMangoId = $selectedPayment->getMangoBuyerId();
        $sellerMangoId = $selectedPayment->getMangoSellerId();
        $debitedWalletId = $selectedPayment->getMangoBuyerWalletId();
        $creditedWalletId = $selectedPayment->getMangoSellerWalletId();
        $buyer = $userRepository->findBy(["mangoUserId" => $buyerMangoId])[0];
        $seller = $userRepository->findBy(["mangoUserId" => $sellerMangoId])[0];
        $buyerEmail = $buyer->getEmail();
        $sellerEmail = $seller->getEmail();
        $result = $mangoPayService->createPayInTransfer($buyerMangoId, $amount, $debitedWalletId, $creditedWalletId);
        if($result->Status === "SUCCEEDED")
        {
            $selectedPayment->setStatus('Finished');
            $selectedPayment->setTransferId($result->Id);
            $this->em->persist($selectedPayment);
            $this->em->flush();
            $notificationsService->sendAndSaveNotifications($buyer, "Félicitations! Votre RDV vient d'être accepté");
            $notificationsService->sendAndSaveNotifications($seller, "Félicitations! Vous venez d'accepter votre RDV");
            $this->processSendingSuccessAppointmentEmail("buyer_transfer_done", $buyerEmail, $this->mailer);
            $this->processSendingSuccessAppointmentEmail("seller_transfer_done", $sellerEmail, $this->mailer);
            return $this->json(['msg' => "payment credited to the seller's wallet successfully"], 200);
        } else{
            return $this->json(['msg' => $result->ResultMessage], 409);
        }

    }


    // Create Refund from Buyer Wallet to Buyer Credit card
    #[Route('/api/payment/create-refund', name: 'create_refund')]
    public function createRefund(MangoPayService $mangoPayService, Request $request, PaymentRepository $paymentRepository, UserRepository $userRepository, NotificationsService $notificationsService): Response
    {
        $paymentId = (int) $request->query->get("paymentId");
        $selectedPayment = $paymentRepository->find($paymentId);
        $amount = floatval($selectedPayment->getAmount() * 100);
        $payInId = $selectedPayment->getPayinId();
        $buyerMangoId = $selectedPayment->getMangoBuyerId();
        $sellerMangoId = $selectedPayment->getMangoSellerId();
        $buyer = $userRepository->findBy(["mangoUserId" => $buyerMangoId])[0];
        $seller = $userRepository->findBy(["mangoUserId" => $sellerMangoId])[0];
        $buyerEmail = $buyer->getEmail();
        $sellerEmail = $seller->getEmail();
        $result = $mangoPayService->createPayInRefund($payInId, $buyerMangoId, $amount);
        if($result->Status === "SUCCEEDED")
        {
            $selectedPayment->setStatus('Refunded');
            $selectedPayment->setRefundId($result->Id);
            $this->em->persist($selectedPayment);
            $this->em->flush();
            $notificationsService->sendAndSaveNotifications($buyer, "Malheureusement, Votre RDV vient d'être annulé");
            $notificationsService->sendAndSaveNotifications($seller, "Malheureusement, Vous venez d'annuler votre RDV");
            $this->processSendingSuccessAppointmentEmail("buyer_refund_done", $buyerEmail, $this->mailer);
            $this->processSendingSuccessAppointmentEmail("seller_refund_done", $sellerEmail, $this->mailer);
            return $this->json(['msg' => "payment refunded to the buyer's credit card successfully"], 200);
        } else
        {
            return $this->json(['msg' => $result->ResultMessage], 409);
        }

    }

    // Create Refund from Buyer Wallet to Buyer Credit card
    #[Route('/api/users/payments/{userId}', name: 'get_payment_infos')]
    public function getUserPaymentInfos($userId, PaymentRepository $paymentRepository): Response
    {
        return $this->json($paymentRepository->findBy(["sellerId" => $userId]));
    }

    public function processSendingSuccessAppointmentEmail(string $context, string $email, MailerInterface $mailer)
    {
        $email = (new TemplatedEmail())
            ->from(new Address('support@coiffme.fr', 'Service Client CoiffMe'))
            ->to($email)
            ->subject('Informations RDV');
            if($context === "buyer_payment_done")
            {
                /*dd("buyer");*/
                $email->htmlTemplate('appointment/success_payment_email_buyer.html.twig');
            }
            elseif ($context === "seller_payment_done")
            {
                $email->htmlTemplate('appointment/success_payment_email_seller.html.twig');
            }
            elseif ($context === "buyer_transfer_done")
            {
                $email->htmlTemplate('appointment/success_transfer_email_buyer.html.twig');
            }
            elseif ($context === "seller_transfer_done")
            {
                $email->htmlTemplate('appointment/success_transfer_email_seller.html.twig');
            }
            elseif ($context === "buyer_refund_done")
            {
                $email->htmlTemplate('appointment/success_refund_email_buyer.html.twig');
            }
            elseif ($context === "seller_refund_done")
            {
                $email->htmlTemplate('appointment/success_refund_email_seller.html.twig');
            }
        ;
        $mailer->send($email);
    }

    public function processSendingSuccessPaymentEmail(Payment $payment, MailerInterface $mailer, UserRepository $userRepository)
    {
        /*$buyerId = (int) $payment->getBuyerId();*/
        $buyer = $userRepository->find((int) $payment->getBuyerId());
        $email = (new TemplatedEmail())
            ->from(new Address('support@coiffme.fr', 'Service Client CoiffMe'))
            ->to($buyer->getEmail())
            ->subject('Notification de paiement')
            ->htmlTemplate('appointment/success_payment_email_buyer.html.twig')
            ->context(
                [
                    'buyer' => $buyer,
                    'payment' => $payment
                ]
            )
        ;
        $mailer->send($email);
    }

}
