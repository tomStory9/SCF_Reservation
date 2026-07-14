<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Email;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'register.email',
                'attr' => [
                    'placeholder' => 'register.email_placeholder',
                ],
                'translation_domain' => 'forms',
                'required' => true,
                'constraints' => [
                    new NotBlank(
                        message: 'register.email_not_blank',
                    ),
                    new Email(
                        message:'register.email_invalid',
                    ),
                ],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'invalid_message' => 'register.password_mismatch',
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
                        message: 'register.password_not_blank'
                    ),
                    new Length(min: 8),
                    new Regex(pattern: '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', message: 'register.password_invalid') // regex pattern 8 characters, at least one uppercase letter, one lowercase letter, one number and one special character
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}