<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ChangePasswordFormType;
use App\Form\ResetPasswordRequestFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\ResetPassword\Controller\ResetPasswordControllerTrait;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

#[Route('/reset-password')]
class ResetPasswordController extends AbstractController
{
    use ResetPasswordControllerTrait;

    public function __construct(
        private ResetPasswordHelperInterface $resetPasswordHelper,
        private EntityManagerInterface $entityManager
    ){
    }

    /**
     * Affichage et formulaire de demande de réinitialisation de mot de passe
     */
    #[Route('', name: 'app_forgot_password_request')]
    public function request(Request $request, MailerInterface $mailer, TranslatorInterface $translator): Response{
        /* Si l'utilisateur est connecté, il ne peut pas accéder à la page */
        if($this->getUser()){
            return $this->redirectToRoute('app_home');
        }

        /* Création du formulaire */
        $form = $this->createForm(ResetPasswordRequestFormType::class);
        /* Vérification de la requête qui permet de verifier si le formulaire est soumis */
        $form->handleRequest($request);

        /* Si le formulaire est soumis et est valide (données entrées sont correct) */
        if($form->isSubmitted() && $form->isValid()){
            /* On fait l'envoi d'un email pour recréer un mot de passe */
            return $this->processSendingPasswordResetEmail(
                $form->get('email')->getData(),
                $mailer,
                $translator
            );
        }

        return $this->render('reset_password/request.html.twig', [
            'requestForm' => $form->createView(),
        ]);
    }

    /**
     * Page de confirmation de la demande de réinitialisation de mot de passe
     */
    #[Route('/check-email', name: 'app_check_email')]
    public function checkEmail(): Response{
        /* Si l'utilisateur est connecté, il ne peut pas accéder à la page */
        if($this->getUser()){
            return $this->redirectToRoute('app_home');
        }

        /* Génération d'un faux token si jamais l'utilisateur n'existe pas ou si quelqu'un accède à la page alors qu'il ne le devrait pas */
        /* On évite de réveler si un utilisateur existe avec l'email */
        if(null === ($resetToken = $this->getTokenObjectFromSession())){
            $resetToken = $this->resetPasswordHelper->generateFakeResetToken();
        }

        return $this->render('reset_password/check_email.html.twig', [
            'resetToken' => $resetToken,
        ]);
    }

    /**
     * Vérification de l'URL (plus précisement du token) et traitement du changement de mot de passe
     */
    #[Route('/reset/{token}', name: 'app_reset_password')]
    public function reset(Request $request, UserPasswordHasherInterface $passwordHasher, TranslatorInterface $translator, string $token = null): Response{
        /* Si il y a un token */
        if($token){
            /* On le stocke en session et on le retire de l'URL pour éviter tout danger */
            $this->storeTokenInSession($token);

            return $this->redirectToRoute('app_reset_password');
        }

        /* On récupère le token stocké en session */
        $token = $this->getTokenFromSession();
        /* Si on ne trouve pas de token */
        if(null === $token){
            /* On crée une erreur 404 */
            throw $this->createNotFoundException('No reset password token found in the URL or in the session.');
        }

        try{
            /* On essaie de valider le token et de trouver l'utilisateur associé */
            $user = $this->resetPasswordHelper->validateTokenAndFetchUser($token);
        }catch(ResetPasswordExceptionInterface $e){
            /* Si il y a une erreur */
            /* On l'indique */
            $this->addFlash('reset_password_error', sprintf(
                '%s - %s',
                $translator->trans(ResetPasswordExceptionInterface::MESSAGE_PROBLEM_VALIDATE, [], 'ResetPasswordBundle'),
                $translator->trans($e->getReason(), [], 'ResetPasswordBundle')
            ));

            return $this->redirectToRoute('app_forgot_password_request');
        }

        /* Le token est validé */
        /* Création du formulaire */
        $form = $this->createForm(ChangePasswordFormType::class);
        /* Vérification de la requête qui permet de verifier si le formulaire est soumis */
        $form->handleRequest($request);

        /* Si le formulaire est soumis et est valide (données entrées sont correct) */
        if($form->isSubmitted() && $form->isValid()){
            /* On enlève le token utilisé (les tokens doivent être utilisé une seule fois) */
            $this->resetPasswordHelper->removeResetRequest($token);

            /* Hashage du mot de passe */
            $encodedPassword = $passwordHasher->hashPassword(
                $user,
                $form->get('plainPassword')->getData()
            );

            /* On effectue le changement et on le sauvegarde en base de données */
            $user->setPassword($encodedPassword);
            $this->entityManager->flush();

            /* On nettoie la session après la réinitialisation du mot de passe */
            $this->cleanSessionAfterReset();

            return $this->redirectToRoute('app_login');
        }

        return $this->render('reset_password/reset.html.twig', [
            'resetForm' => $form->createView(),
        ]);
    }

    private function processSendingPasswordResetEmail(string $emailFormData, MailerInterface $mailer, TranslatorInterface $translator): RedirectResponse{
        /* Recherche d'un utilisateur avec l'email indiqué */
        $user = $this->entityManager->getRepository(User::class)->findOneBy([
            'email' => $emailFormData,
        ]);

        /* On n'indique pas qu'un utilisateur est trouvé, on le redirige sans le dire */
        if(!$user){
            return $this->redirectToRoute('app_check_email');
        }

        try{
            $resetToken = $this->resetPasswordHelper->generateResetToken($user);
        }catch(ResetPasswordExceptionInterface $e){
            // If you want to tell the user why a reset email was not sent, uncomment
            // the lines below and change the redirect to 'app_forgot_password_request'.
            // Caution: This may reveal if a user is registered or not.
            //
            // $this->addFlash('reset_password_error', sprintf(
            //     '%s - %s',
            //     $translator->trans(ResetPasswordExceptionInterface::MESSAGE_PROBLEM_HANDLE, [], 'ResetPasswordBundle'),
            //     $translator->trans($e->getReason(), [], 'ResetPasswordBundle')
            // ));

            return $this->redirectToRoute('app_check_email');
        }

        /* Création de l'email */
        $email = (new TemplatedEmail())
            ->from(new Address('admin@AnimeProjetElan.com', 'Admin Anime Projet Elan'))
            ->to($user->getEmail())
            ->subject('Your password reset request')
            ->htmlTemplate('reset_password/email.html.twig')
            ->context([
                'resetToken' => $resetToken,
            ])
        ;

        /* Envoie de l'email */
        $mailer->send($email);

        /* On stock le token associé en session pour le récupérer dans la route 'app_check_email' */
        $this->setTokenObjectInSession($resetToken);

        return $this->redirectToRoute('app_check_email');
    }
}
