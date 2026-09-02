<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

class ResetPasswordRequestFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'request.email',
                'translation_domain' => 'reset_password',
                'attr' => [
                    'autocomplete' => 'email',
                    'placeholder' => 'request.email_placeholder',
                ],
                'constraints' => [
                    new NotBlank(
                        message: 'errors.email_not_blank',
                    ),
                    new Email(
                        message: 'errors.email_invalid',
                    ),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
