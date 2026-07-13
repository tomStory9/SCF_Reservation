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
            ->add('name',TextType::class,
            [
                'label' => 'form.name.label',
                'attr' => [
                    'placeholder' => 'form.name.placeholder',
                ],
                'required' => true,
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
                'required' => true,
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
                'required' => true,
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
