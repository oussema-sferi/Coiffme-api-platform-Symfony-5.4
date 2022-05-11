<?php
namespace App\Services;
use http\Client\Request;
use MangoPay;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Contracts\HttpClient\HttpClientInterface;


class MangoPayService
{

    private MangoPay\MangoPayApi $mangoPayApi;
    private $buyerMangoId;
    private $sellerMangoId;
    private $amount;
    private $currency;

    public function __construct(ParameterBagInterface $bag, private SessionInterface $session)
    {
        $this->mangoPayApi = new MangoPay\MangoPayApi();
        $this->mangoPayApi->Config->ClientId = 'tooeasy';
        $this->mangoPayApi->Config->ClientPassword = 'LC07WjG7FEMF7TZaoJwyWeD7pXXF5Rh6h6F93SxTNdiDAmLbNq';
        $this->mangoPayApi->Config->BaseUrl = 'https://api.sandbox.mangopay.com';
        /*dd(str_replace('\\public\\', '', $_SERVER['DOCUMENT_ROOT']));*/
        /*$this->mangoPayApi->Config->TemporaryFolder = str_replace('\\public\\', '', $_SERVER['DOCUMENT_ROOT']) . '/var/tmp';*/
        $this->mangoPayApi->Config->TemporaryFolder = $bag->get('kernel.project_dir') . '/var/tmp';
        //
        $this->buyerMangoId = $this->session->get('buyer_mango_id');
        $this->sellerMangoId = $this->session->get('seller_mango_id');
        $this->amount = $this->session->get('amount');
        $this->currency = 'EUR';
    }

    public function registerCard($buyerMangoId, $currency, $amount)
    {
        $newRegisteredCard = new MangoPay\CardRegistration();
        $newRegisteredCard->UserId = $buyerMangoId;
        $newRegisteredCard->Currency = 'EUR';
        $newRegisteredCard->CardType = 'CB_VISA_MASTERCARD';
        $result = $this->mangoPayApi->CardRegistrations->Create($newRegisteredCard);
        /*dd($result);*/
        $this->session->set('card_id', $result->Id);
        return $result;
    }

    public function createPayInForUserWallet($naturalUserId, $userWalletId)
    {
        $registeredCard = $this->mangoPayApi->CardRegistrations->Get($this->session->get('card_id'));
        $registeredCard->RegistrationData = "data=" . $_GET["data"];
        if($registeredCard->CardId === null)
        {
            $registeredCard= $this->mangoPayApi->CardRegistrations->Update($registeredCard);
        }
        //virtual card
        $card = $this->mangoPayApi->Cards->Get($registeredCard->CardId);
        /*dd($card);*/
        //execute payIn
        $PayIn = new \MangoPay\PayIn();
        $PayIn->CreditedWalletId = $userWalletId;
        $PayIn->AuthorId = $naturalUserId;
        $PayIn->DebitedFunds = new \MangoPay\Money();
        $PayIn->DebitedFunds->Currency = 'EUR';
        $PayIn->DebitedFunds->Amount = $this->amount * 100;
        $PayIn->Fees = new \MangoPay\Money();
        $PayIn->Fees->Currency = 'EUR';
        $PayIn->Fees->Amount = 0;
        // payment type as CARD
        $PayIn->PaymentDetails = new \MangoPay\PayInPaymentDetailsCard();
        $PayIn->PaymentDetails->CardType = $card->CardType;
        $PayIn->PaymentDetails->CardId = $card->Id;
        // execution type as DIRECT
        $PayIn->ExecutionDetails = new \MangoPay\PayInExecutionDetailsDirect();
        $secModeReturnUrl = 'http' . ( isset($_SERVER['HTTPS']) ? 's' : '' ) . '://' . $_SERVER['HTTP_HOST'];
        $secModeReturnUrl .= substr($_SERVER['REQUEST_URI'], 0, strripos($_SERVER['REQUEST_URI'], '/') + 1);
        $secModeReturnUrl .= 'check';
        $PayIn->ExecutionDetails->SecureModeReturnURL = $secModeReturnUrl;
        $PayIn->ExecutionDetails->SecureMode = 'DEFAULT';
        $result = $this->mangoPayApi->PayIns->Create($PayIn);
        /*dd($result);*/
        return $result;
    }

    public function createPayInTransfer($buyerMangoId, $amount, $debitedWalletId, $creditedWalletId)
    {
        $Transfer = new \MangoPay\Transfer();
        $Transfer->AuthorId = $buyerMangoId;
        $Transfer->DebitedFunds = new \MangoPay\Money();
        $Transfer->DebitedFunds->Currency = 'EUR';
        $Transfer->DebitedFunds->Amount = $amount;
        $Transfer->Fees = new \MangoPay\Money();
        $Transfer->Fees->Currency = 'EUR';
        $Transfer->Fees->Amount = 0;
        $Transfer->DebitedWalletId = $debitedWalletId;
        $Transfer->CreditedWalletId = $creditedWalletId;
        $result = $this->mangoPayApi->Transfers->Create($Transfer);
        return $result;
    }

    public function createPayInRefund($payInId, $buyerMangoId, $amount)
    {
        $PayInId = $payInId;
        $Refund = new \MangoPay\Refund();
        $Refund->AuthorId = $buyerMangoId;
        $Refund->DebitedFunds = new \MangoPay\Money();
        $Refund->DebitedFunds->Currency = 'EUR';
        $Refund->DebitedFunds->Amount = $amount;
        $Refund->Fees = new \MangoPay\Money();
        $Refund->Fees->Currency = 'EUR';
        $Refund->Fees->Amount = 0;
        $result = $this->mangoPayApi->PayIns->CreateRefund($PayInId, $Refund);
        return $result;
    }



    public function createNaturalUser($firstName, $lastName, $email)
    {
        $newUser = new \MangoPay\UserNatural();
        $newUser->Email = $email;
        $newUser->FirstName = $firstName;
        $newUser->LastName = $lastName;
        $newUser->Birthday = 121271;
        $newUser->Nationality = "FR";
        $newUser->CountryOfResidence = "FR";
        $result = $this->mangoPayApi->Users->Create($newUser);
        return $result->Id;
    }

    public function createWalletForNaturalUser($naturalUserId)
    {
        $Wallet = new \MangoPay\Wallet();
        $Wallet->Owners = array($naturalUserId);
        $Wallet->Description = "CoiffMe Wallet";
        $Wallet->Currency = "EUR";
        $result = $this->mangoPayApi->Wallets->Create($Wallet);
        return $result->Id;
    }

    public function checkPayInStatus($payInId)
    {
        return $this->mangoPayApi->PayIns->Get($payInId);
    }
}