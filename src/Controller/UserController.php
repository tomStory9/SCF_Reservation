<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ChangePasswordFormType;
use App\Form\UserFormType;
use App\Repository\UserRepository;
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
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

final class UserController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly EmailVerifier $emailVerifier,
        private readonly TranslatorInterface $translator,
    ) {
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/user', name: 'app_home_user')]
    public function index(): Response
    {
        $user = $this->getUser();

        return $this->render('user/index.html.twig', [
            'user' => $user,
            'bookings' => $user->getBookings(),
        ]);
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/profil', name: 'app_user_profile')]
    public function profile(): Response
    {
        $user = $this->getUser();

        return $this->render('user/informations.html.twig', [
            'user' => $user,
        ]);
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/profil/modifier', name: 'app_user_profile_edit')]
    public function editProfile(
        #[Autowire(env: 'MAILER_ADDRESS')]
        string $mailerAddress,
        Request $request,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        $originalEmail = $user->getEmail();

        $form = $this->createForm(UserFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $emailChanged = $originalEmail !== $user->getEmail();

            if ($emailChanged) {
                $existingUser = $this->userRepository->findOneBy(['email' => $user->getEmail()]);
                if ($existingUser && $existingUser->getId() !== $user->getId()) {
                    $form->get('email')->addError(
                        new FormError('flash.error_email')
                    );

                    return $this->render('user/edit_informations.html.twig', [
                        'form' => $form->createView(),
                    ]);
                }

                $user->setIsVerified(false);

                $this->emailVerifier->sendEmailConfirmation(
                    'app_verify_email',
                    $user,
                    new TemplatedEmail()
                        ->from(new Address($mailerAddress, 'Setoushi Circus Factory'))
                        ->to($user->getEmail())
                        ->subject('メールアドレスの確認をお願いします')
                        ->htmlTemplate('security/register/mails/confirmation_email.html.twig')
                );

                $this->addFlash('warning', $this->translator->trans('flash.email_changed_warning'));
            } else {
                $this->addFlash('success', $this->translator->trans('flash.update_success'));
            }

            $this->entityManager->flush();

            return $this->redirectToRoute('app_user_profile');
        }

        return $this->render('user/edit_informations.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/profil/mot-de-passe', name: 'app_user_change_password')]
    public function changePassword(Request $request, UserPasswordHasherInterface $passwordHasher): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $form = $this->createForm(ChangePasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newPassword = $form->get('plainPassword')->getData();

            $user->setPassword($passwordHasher->hashPassword($user, $newPassword));

            $this->entityManager->flush();

            $this->addFlash('success', $this->translator->trans('account.password.update_success', domain: 'profile'));

            return $this->redirectToRoute('app_user_profile');
        }

        return $this->render('user/change_password.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
