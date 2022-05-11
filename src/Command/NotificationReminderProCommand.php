<?php

namespace App\Command;

use App\Repository\AppointmentRepository;
use App\Services\EmailsSendingService;
use App\Services\MangoPayService;
use App\Services\NotificationsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use App\Repository\NotificationRepository;
use App\Entity\Notification;
use App\Entity\Appointment;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class NotificationReminderProCommand extends Command
{
    protected static $defaultName = 'app:appointment:handle';

    public function __construct(
        private HttpClientInterface $client,
        private AppointmentRepository $appointmentRepository,
        private NotificationRepository $notificationRepository,
        private NotificationsService $notificationsService,
        private MangoPayService $mangoPayService,
        private EmailsSendingService $emailsSendingService,
        private MailerInterface $mailer,
        private EntityManagerInterface $entityManager
    )
    {
        parent::__construct();
    }
    protected function configure()
    {
        $this->setDescription('Handle Professional User Push Notification Reminder');
    }
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $allPendingAppointments = $this->appointmentRepository->getCreatedAndPendingAppointments();
        foreach ($allPendingAppointments as $pendingAppointment)
        {
            // Handle Seller Push Notification Reminder Before 5 Minutes
            if($pendingAppointment->getCreatedAt()->format('d-m-Y H:i') === (new \DateTimeImmutable('5 min ago'))->format('d-m-Y H:i'))
            {
                $seller = $pendingAppointment->getUser();
                $this->notificationsService->sendAndSaveNotifications($seller, 'Attention, il vous reste 5 minutes pour accepter le rdv et obtenir un bonus');
            }
            // Handle Decline Appointments after 30 minutes Elapsed & Push Notifications and Emails
            if($pendingAppointment->getCreatedAt()->add(new \DateInterval('PT' . 31 . 'M'))->format('d-m-Y H:i') === (new \DateTimeImmutable())->format('d-m-Y H:i'))
            {
                $associatedPayment = $pendingAppointment->getAssociatedPayment();
                $seller = $pendingAppointment->getUser();
                $buyer = $pendingAppointment->getOwner();
                $amount = floatval($associatedPayment->getAmount() * 100);
                $payInId = $associatedPayment->getPayinId();
                $buyerMangoId = $associatedPayment->getMangoBuyerId();
                $sellerMangoId = $associatedPayment->getMangoSellerId();
                $buyerEmail = $buyer->getEmail();
                $sellerEmail = $seller->getEmail();
                $result = $this->mangoPayService->createPayInRefund($payInId, $buyerMangoId, $amount);
                if($result->Status === "SUCCEEDED")
                {
                    $pendingAppointment->setStatus('declined');
                    $associatedPayment->setStatus('Refunded');
                    $associatedPayment->setRefundId($result->Id);
                    $this->entityManager->persist($associatedPayment);
                    $this->entityManager->flush();
                    $this->emailsSendingService->processSendingSuccessAppointmentEmail("buyer_refund_done", $buyerEmail, $this->mailer);
                    $this->emailsSendingService->processSendingSuccessAppointmentEmail("seller_refund_done", $sellerEmail, $this->mailer);
                    $this->notificationsService->sendAndSaveNotifications($seller, 'Votre RDV a été annulé automatiquement suite à son expiration');
                    $this->notificationsService->sendAndSaveNotifications($buyer, "Votre RDV vient d'être Annulé, veuillez choisir un autre professionnel!");
                }
            }
        }
        $acceptedAppointments = $this->appointmentRepository->getAcceptedAppointments();
        foreach ($acceptedAppointments as $acceptedAppointment)
        {
            // Start Date
            $day = $acceptedAppointment->getAvailableDateHour()->getAvailabilityDate()->getDate()->format('d-m-Y');
            $startTime = $acceptedAppointment->getAvailableDateHour()->getStartHour()->format('H:i');
            $newStartDate = $day . " " . $startTime;
            // End Date
            $endTime = $acceptedAppointment->getAvailableDateHour()->getEndHour()->format('H:i');
            $newEndDate = $day . " " . $endTime;
            // Handle Email Reminder For Buyer Before 2 Hours
            if((new \DateTimeImmutable())->add(new \DateInterval('PT' . 120 . 'M'))->format('d-m-Y H:i') === $newStartDate)
                {
                    $buyerEmail = $acceptedAppointment->getOwner()->getEmail();
                    $this->emailsSendingService->processSendingSuccessAppointmentEmail("buyer_before_two_hours_reminder", $buyerEmail, $this->mailer, $day, $startTime);
                }
            // Handle Buyer Push Notification Reminder Before 30 Minutes
            if((new \DateTimeImmutable())->add(new \DateInterval('PT' . 30 . 'M'))->format('d-m-Y H:i') === $newStartDate)
            {
                $buyer = $acceptedAppointment->getOwner();
                $this->notificationsService->sendAndSaveNotifications($buyer, 'Attention votre RDV aura lieu dans 30 minutes');
            }
            // Handle Buyer Push Notification For Feedback Reminder Just 5 minutes After of The Appointment And After 24 Hours
            if(
                ($acceptedAppointment->getUserStatus() === "accepted" && ((new \DateTimeImmutable())->sub(new \DateInterval('PT' . 5 . 'M'))->format('d-m-Y H:i') === $newEndDate))
                ||
                ($acceptedAppointment->getUserStatus() === "accepted" && ((new \DateTimeImmutable())->sub(new \DateInterval('PT' . 24 . 'H'))->format('d-m-Y H:i') === $newEndDate))
            )
            {
                $buyer = $acceptedAppointment->getOwner();
                $this->notificationsService->sendAndSaveNotifications($buyer, 'Rappel: veuillez mettre un avis et un pourboir pour le professionnel');
            }
        }
        $io->success(sprintf('Tasks Handled Successfully!'));
        return 0;
    }
}