<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Form\UserFormType;
use App\Security\EmailVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

final class RegisterController extends AbstractController
{
    public function __construct(
        private EmailVerifier $emailVerifier
    ) {
    }

    #[Route('/register', name: 'app_register')]
    public function register(
        #[Autowire(env: 'MAILER_ADDRESS')]
        string $mailerAddress,
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        TranslatorInterface $translator
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        $user = new User();

        $form = $this->createForm(RegistrationFormType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($entityManager->getRepository(User::class)->findOneBy([
                'email' => $user->getEmail(),
            ])) {
                $form->get('email')->addError(
                    new FormError(
                        $translator->trans('flash.error_email', domain: 'validators')
                    )
                );

                return $this->render('security/register/index.html.twig', [
                    'registrationForm' => $form,
                ]);
            }

            $user->setPassword(
                $passwordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );
            $user->setFilledInfo(false);
            $user->setCompany(null);
            $user->setPhone('');
            $user->setName('');
            $user->setLastname('');
            $user->setIsVerified(false);

            $entityManager->persist($user);
            $entityManager->flush();
            $this->emailVerifier->sendEmailConfirmation(
                'app_verify_email',
                $user,
                new TemplatedEmail()
                    ->from(new Address($mailerAddress, 'Setoushi Circus Factory'))
                    ->to($user->getEmail())
                    ->subject($translator->trans('registration_email.subject'))
                    ->htmlTemplate('security/register/mails/confirmation_email.html.twig')
            );

            $this->addFlash('info', $translator->trans('flash.info_email', domain: 'validators'));

            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/register/index.html.twig', [
            'registrationForm' => $form,
        ]);
    }

    #[Route('/register/information', name: 'app_register_information')]
    public function info(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $this->getUser();
        if ($user->isFilledInfo()) {
            return $this->redirectToRoute('app_home');
        }

        $form = $this->createForm(UserFormType::class, $user);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setFilledInfo(true);
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('app_home');
        }

        return $this->render('security/register/form.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(Request $request, TranslatorInterface $translator): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED');

        // validate email confirmation link, set User::$isVerified=true, and persist
        try {
            $this->emailVerifier->handleEmailConfirmation($request, $this->getUser());
        } catch (VerifyEmailExceptionInterface $exception) {
            $this->addFlash('verify_email_error', $translator->trans($exception->getReason(), [], 'VerifyEmailBundle'));

            return $this->redirectToRoute('app_register');
        }

        $this->addFlash('success', 'Your email address has been verified.');

        return $this->redirectToRoute('app_register');
    }
}
