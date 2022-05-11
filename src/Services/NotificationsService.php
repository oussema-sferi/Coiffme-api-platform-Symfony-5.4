<?php
namespace App\Services;
use App\Entity\Notification;
use App\Repository\NotificationRepository;
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


class NotificationsService
{
    public function __construct(private HttpClientInterface $httpClient, private  NotificationRepository $notificationRepository)
    {
    }
    public function sendAndSaveNotifications($user, $body)
    {
        if ($token = $user->getTokenExpo()) {
            $response = $this->httpClient->request('POST', 'https://exp.host/--/api/v2/push/send', [
                'json' => ['to' => $token,
                    'channelId' => 'default',
                    'sound' => 'default',
                    'title' => 'Application CoiffMe',
                    'body' => $body,
                ],
            ]);
        }
        $notification = new Notification();
        $notification->setUser($user)->setMessage($body);
        $this->notificationRepository->add($notification);
    }
}