<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ChangePasswordFormType;
use App\Form\ResetPasswordRequestFormType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\ResetPassword\Controller\ResetPasswordControllerTrait;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

#[AsController]
/*#[Route('/api/reset-password')]*/
class ResetPasswordController extends AbstractController
{
    use ResetPasswordControllerTrait;

    public function __construct(
        private ResetPasswordHelperInterface $resetPasswordHelper,
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
    )
    {
    }
    /**
     * Display & process form to request a password reset.
     */

    public function __invoke(Request $request, MailerInterface $mailer, TranslatorInterface $translator): JsonResponse
    {
        $jsonData = json_decode($request->getContent());
        $user = $this->userRepository->findBy(["email" => $jsonData->email]);
        if($user) {
            return $this->processSendingPasswordResetEmail(
                $jsonData->email,
                $mailer,
                $translator
            );
        } else {
            return $this->json([
                "message" => "Cet utilisateur n'existe pas!"
            ], 404);
        }
    }

    /**
     * Validates and process the reset URL that the user clicked in their email.
     */
    #[Route('/reset/{token}', name: 'app_reset_password', defaults: ['token' => null])]
    public function reset(Request $request, UserPasswordHasherInterface $userPasswordHasher, string $token = null, TranslatorInterface $translator): Response
    {
        if ($token) {
            // We store the token in session and remove it from the URL, to avoid the URL being
            // loaded in a browser and potentially leaking the token to 3rd party JavaScript.
            $this->storeTokenInSession($token);
            return $this->redirectToRoute('app_reset_password');
        }
        $token = $this->getTokenFromSession();
        if (null === $token) {
            return $this->render('reset_password/invalid_reset_link.html.twig');
            /*throw $this->createNotFoundException('No reset password token found in the URL or in the session.');*/
        }
        try {
            $user = $this->resetPasswordHelper->validateTokenAndFetchUser($token);
        } catch (ResetPasswordExceptionInterface $e) {
            return $this->render('reset_password/invalid_reset_link.html.twig');
        }
        // The token is valid; allow the user to change their password.
        $form = $this->createForm(ChangePasswordFormType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // A password reset token should be used only once, remove it.
            $this->resetPasswordHelper->removeResetRequest($token);
            // Encode(hash) the plain password, and set it.
            $encodedPassword = $userPasswordHasher->hashPassword(
                $user,
                $form->get('plainPassword')->getData()
            );
            $user->setPassword($encodedPassword);
            $this->entityManager->flush();
            // The session is cleaned up after the password has been changed.
            $this->cleanSessionAfterReset();
            return $this->redirectToRoute('update_password_successful');
        }
        return $this->render('reset_password/reset.html.twig', [
            'resetForm' => $form->createView(),
        ]);
    }

    private function processSendingPasswordResetEmail(string $emailFormData, MailerInterface $mailer, TranslatorInterface $translator): JsonResponse
    {
        $user = $this->userRepository->findOneBy([
            'email' => $emailFormData,
        ]);
        // Do not reveal whether a user account was found or not.
        if (!$user) {
            return $this->json([
                "message" => "User Not Found!"
            ], 404);
        }
        try {
            $resetToken = $this->resetPasswordHelper->generateResetToken($user);
        } catch (ResetPasswordExceptionInterface $e) {
            return $this->json([
                "message" => $translator->trans($e->getReason(), [], 'ResetPasswordBundle')
            ], 403);
        }
        $email = (new TemplatedEmail())
            ->from(new Address('support@coiffme.fr', 'Service Client CoiffMe'))
            ->to($user->getEmail())
            ->subject('Demande de réinitialisation de votre mot de passe')
            ->htmlTemplate('reset_password/email.html.twig')
            ->context([
                'resetToken' => $resetToken,
            ])
        ;
        $mailer->send($email);
        $this->setTokenObjectInSession($resetToken);
        return $this->json([
            "message" => "un email de réinitialisation du mot de passe vient de vous être envoyé! Veuillez vérifier votre boîte mail."
        ], 200);
    }

    #[Route('/update-password/success', name: 'update_password_successful')]
    public function successfulUpdatePassword(): Response
    {
        return $this->render('reset_password/password_updated_successfully.html.twig');
    }

}
