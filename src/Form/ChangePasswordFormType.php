<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class ChangePasswordFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('currentPassword', PasswordType::class, [
                'label' => 'account.password.current',
                'translation_domain' => 'profile',
                'mapped' => false,
                'constraints' => [
                    new NotBlank(
                        message: 'errors.current_password_not_blank',
                    ),
                    new UserPassword(
                        message: 'errors.invalid_current',
                    ),
                ],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'invalid_message' => 'errors.password_mismatch',
                'first_options' => [
                    'label' => 'register.password',
                    'attr' => [
                        'placeholder' => 'register.password_placeholder',
                    ],
                    'translation_domain' => 'forms',
                ],
                'second_options' => [
                    'label' => 'register.password_confirmation',
                    'attr' => [
                        'placeholder' => 'register.password_confirmation_placeholder',
                    ],
                    'translation_domain' => 'forms',
                ],
                'constraints' => [
                    new NotBlank(
                        message: 'errors.password_not_blank'
                    ),
                    new Length(min: 8, minMessage: 'errors.password_too_short'),
                    new Regex(pattern: '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*\W)\S{8,}$/', message: 'errors.password_invalid'), // regex pattern 8 characters, at least one uppercase letter, one lowercase letter, one number and one special character
                ],
            ]);
    }
}
