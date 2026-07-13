<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Email;

class UserFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email',EmailType::class,
            [   
                'label' => 'form.email.label',
                'attr' => [
                    'placeholder' => 'form.email.placeholder',
                ],
                'required' => true,
                'empty_data' => '',
                'constraints' => [
                new NotBlank([
                    'message' => 'form.email.not_blank',
                ]),
                new Email([
                    'message' => 'form.email.invalid',
                ]),
                ],
            ])
            ->add('plainPassword',PasswordType::class,
            [   'mapped' => false,
                'label' => 'form.password.label',
                'attr' => [
                    'placeholder' => 'form.password.placeholder',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'form.password.not_blank',
                    ]),
                    new Length([
                        'min' => 10,
                        'minMessage' => 'form.password.min_length',
                    ]),
                    new Regex([
                        'pattern' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{10,}$/', // At least one uppercase letter, one lowercase letter, one digit, and one special character
                        'message' => 'form.password.pattern',
                    ]),
                ],
            ])
            ->add('name',TextType::class,
            [
                'label' => 'form.name.label',
                'attr' => [
                    'placeholder' => 'form.name.placeholder',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'form.name.not_blank',
                    ]),
                ],
            ])
            ->add('lastname',TextType::class,
            [
                'label' => 'form.lastname.label',
                'attr' => [
                    'placeholder' => 'form.lastname.placeholder',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'form.lastname.not_blank',
                    ]),
                ],
            ])
            ->add('phone',TelType::class,
            [
                'label' => 'form.phone.label',
                'attr' => [
                    'placeholder' => 'form.phone.placeholder',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'form.phone.not_blank',
                    ]),
                ],
            ])
            ->add('company',TextType::class,
            [
                'label' => 'form.company.label',
                'attr' => [
                    'placeholder' => 'form.company.placeholder',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
