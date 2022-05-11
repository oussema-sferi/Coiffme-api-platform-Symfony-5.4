<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Services\EmailsSendingService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
class UserAccountController extends AbstractController
{
    public function __construct(
        private EmailsSendingService $emailsSendingService,
        private MailerInterface $mailer,
        private EntityManagerInterface $entityManager
    )
    {
    }
    /*#[Route('/api/account/{id}/delete-email-sending', name: 'user_account_deletion_email_send')]
    public function deleteEmailSending($id, UserRepository $userRepository): Response
    {
        $userEmail = $userRepository->find($id)->getEmail();
        $deletionUrl = 'http' . ( isset($_SERVER['HTTPS']) ? 's' : '' ) . '://' . $_SERVER['HTTP_HOST'];
        $deletionUrl .= '/account/' . $id . '/delete';
        $this->emailsSendingService->processSendingSuccessAppointmentEmail("user_account_deletion_email", $userEmail, $this->mailer, null, null, $deletionUrl);
        return $this->json([
            'message' => 'Email sent successfully',
        ], 200);
    }*/

    #[Route('/account/{id}/delete', name: 'user_account_deletion')]
    public function userAccountDeletion($id, UserRepository $userRepository): Response
    {
        $userToDelete = $userRepository->find($id);
        $userToDeleteEmail = $userToDelete->getEmail();
        $userToDelete->setIsDeleted(true);
        $this->entityManager->persist($userToDelete);
        $this->entityManager->flush();
        $this->emailsSendingService->processSendingSuccessAppointmentEmail("user_account_deletion_success", $userToDeleteEmail, $this->mailer);
        /*return $this->redirectToRoute('user_account_deletion_confirmation');*/
        return $this->json([
            'message' => 'User deleted successfully and email confirmation sent',
        ], 200);
    }

    /*#[Route('/account/delete/success', name: 'user_account_deletion_confirmation')]
    public function accountDeletionConfirmation(): Response
    {
        return $this->render('user_account/account_deletion_success.html.twig');
    }*/
}
