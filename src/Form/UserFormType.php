<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

class UserFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'email',
                EmailType::class,
                [
                    'label' => 'information.email.label',
                    'attr' => [
                        'placeholder' => 'information.email.placeholder',
                    ],
                    'translation_domain' => 'forms',

                    'required' => true,
                    'empty_data' => '',
                    'constraints' => [
                        new NotBlank(
                            message: 'information.email.not_blank',
                        ),
                        new Email(
                            message: 'information.email.invalid',
                        ),
                    ],
                ]
            )
            ->add(
                'name',
                TextType::class,
                [
                    'label' => 'information.name.label',
                    'translation_domain' => 'forms',
                    'attr' => [
                        'placeholder' => 'information.name.placeholder',
                    ],
                    'required' => true,
                    'constraints' => [
                        new NotBlank(
                            message: 'information.name.not_blank',
                        ),
                    ],
                ]
            )
            ->add(
                'lastname',
                TextType::class,
                [
                    'translation_domain' => 'forms',
                    'label' => 'information.lastname.label',
                    'attr' => [
                        'placeholder' => 'information.lastname.placeholder',
                    ],
                    'required' => true,
                    'constraints' => [
                        new NotBlank(
                            message: 'information.lastname.not_blank',
                        ),
                    ],
                ]
            )
            ->add(
                'phone',
                TelType::class,
                ['translation_domain' => 'forms',
                    'label' => 'information.phone.label',
                    'attr' => [
                        'placeholder' => 'information.phone.placeholder',
                    ],
                    'required' => true,
                    'constraints' => [
                        new NotBlank(
                            message: 'information.phone.not_blank',
                        ),
                    ],
                ]
            )
            ->add(
                'company',
                TextType::class,
                ['translation_domain' => 'forms',
                    'label' => 'information.company.label',
                    'attr' => [
                        'placeholder' => 'information.company.placeholder',
                    ],
                ]
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
