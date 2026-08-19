<?php

namespace App\Controller\Admin;

use App\Entity\Zone;
use App\Enum\ZoneType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[IsGranted('ROLE_ADMIN')]
class ZoneCrudController extends AbstractCrudController
{
    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    public static function getEntityFqcn(): string
    {
        return Zone::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('name', 'admin.field.name');
        yield ChoiceField::new('typeZone', 'admin.field.type')
            ->setFormType(EnumType::class)
            ->setFormTypeOptions([
                'class' => ZoneType::class,
            ])
            ->formatValue(fn ($value) => null === $value
                ? null
                : $this->translator->trans('admin.enum.zone_type.'.$value->value));
        yield AssociationField::new('facility', 'admin.field.facility')
            ->formatValue(fn ($value, $entity) => $value?->getName())
            ->setFormTypeOption('choice_label', 'name');
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('admin.entity.zone')
            ->setEntityLabelInPlural('admin.entity.zones');
    }
}
