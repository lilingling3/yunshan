<?php

namespace Auto\Bundle\ManagerBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class CouponKindType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name','text',['label'  => '优惠名称'])
            ->add('validDay','text',['label'  => '有效天数'])
            ->add('amount','text',['label'  => '金额'])
            ->add('needHour','text',['label'  => '优惠条件最低租赁时常'])
            ->add('needAmount','text',['label'  => '优惠条件最低租赁金额'])
            ->add('carLevel','entity', [
                'required' => false,
                'label'     => '车辆级别',
                'class'     => 'AutoManagerBundle:CarLevel',
                'property'  => 'name',
                'placeholder' => '选择级别',
                'empty_data'  => null
            ])
        ;
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Auto\Bundle\ManagerBundle\Entity\CouponKind'
        ));
    }


    /**
     * @return string
     */
    public function getName()
    {
        return 'auto_bundle_managerbundle_couponkind';
    }
}
