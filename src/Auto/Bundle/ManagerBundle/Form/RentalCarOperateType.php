<?php

namespace Auto\Bundle\ManagerBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class RentalCarOperateType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('licensePlate','text',['label' => '车牌号'])
            ->add('engineNumber','text',['label' => '发动机号'])
            ->add('chassisNumber','text',['label' => '车架号'])
            ->add('car','entity', [
                'label'     => '车型',
                'class'     => 'AutoManagerBundle:Car',
                'property'  => 'name'
            ])
            ->add('rentalStation','entity', [
                'label'     => '租赁点',
                'class'     => 'AutoManagerBundle:RentalStation',
                'property'  => 'name'
            ])
            ->add('company','entity', [
                'label'     => '归属公司',
                'class'     => 'AutoManagerBundle:Company',
                'property'  => 'name'
            ])
            ->add('licensePlace','entity', [
                'label'     => '车牌归属地',
                'class'     => 'AutoManagerBundle:LicensePlace',
                'property'  => 'name'
            ])
            ->add('color','entity', [
                'label'     => '颜色',
                'class'     => 'AutoManagerBundle:Color',
                'property'  => 'name'
            ])
        ;
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Auto\Bundle\ManagerBundle\Entity\RentalCar'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'auto_bundle_managerbundle_operate_rentalcar';
    }
}
