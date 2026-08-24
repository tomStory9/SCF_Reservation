<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class ResetPasswordFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options' => [
                    'label' => 'account.password.new',
                ],
                'second_options' => [
                    'label' => 'account.password.confirm',
                ],
                'invalid_message' => 'account.password.mismatch',
                'mapped' => false,
                'translation_domain' => 'profile',
                'constraints' => [
                    new NotBlank(
                        message: 'errors.password_not_blank'
                    ),
                    new Length(min: 8, minMessage: 'errors.password_too_short'),
                    new Regex(pattern: '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', message: 'errors.password_invalid'), // regex pattern 8 characters, at least one uppercase letter, one lowercase letter, one number and one special character
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
