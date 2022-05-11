<?php

namespace App\Services;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class EmailsSendingService
{
    public function processSendingSuccessAppointmentEmail(string $context, string $email, MailerInterface $mailer, $day = null, $time = null)
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
        elseif ($context === "buyer_before_two_hours_reminder")
        {
            $email->htmlTemplate('appointment/buyer_two_hours_reminder_email.html.twig')
                ->context([
                    'day' => $day,
                    'time' => $time
                ])
            ;
        }
        elseif ($context === "user_account_deletion_success")
        {
            $email->htmlTemplate('user_account/account_deletion_confirmation_email.html.twig')
                ->subject('Confirmation de suppression de compte')
            ;
        }
        ;
        $mailer->send($email);
    }
}