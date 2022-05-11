<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use App\Repository\MessageRepository;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[AsController]
class LatestMessageController extends AbstractController
{
    public function __construct(private Security $security){}
    public function __invoke(MessageRepository $rep, Request $request)
    {
        $conversation = [] ;
        $user = $request->query->get('user');
        $data = $rep->getLastestMessage($user);
        if (is_array($data)) {
            foreach ($data as $message) {
                $recipient = $message->getRecipient();
                $id_recipient = $recipient->getId();
                $conversation[$id_recipient]['id'] = $id_recipient;
                $conversation[$id_recipient]['firstName'] = $recipient->getFirstName();
                $conversation[$id_recipient]['lastName'] = $recipient->getLastName();
                $conversation[$id_recipient]['getPictureFile'] = $recipient->getPictureFile();
                $conversation[$id_recipient]['chat']  = $rep->getConversation($user, $id_recipient);
            }
        }
   
        return $this->json($conversation);
    }
}
