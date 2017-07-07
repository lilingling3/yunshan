<?php

namespace Auto\Bundle\ManagerBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class InsuranceRecordType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('insuranceAmount','text',['label'  => '投保金额'])
            ->add('insuranceNumber','text',['label'  => '投保单号'])
            ->add('insuranceTime', 'date', array(
                'label'  => '投保时间',
                'input'  => 'datetime',
                'widget' => 'single_text','format' => 'yyyy-MM-dd'
            ))
            ->add('startTime', 'date', array(
                'label'  => '保险生效时间',
                'input'  => 'datetime',
                'widget' => 'single_text','format' => 'yyyy-MM-dd'
            ))
            ->add('endTime', 'date', array(
                'label'  => '投保失效时间',
                'input'  => 'datetime',
                'widget' => 'single_text','format' => 'yyyy-MM-dd'
            ))
            ->add('insurance', 'choice', array(
                'label'=>'投保类型',
                'choices'  => array('1' => '交强险','2' => '商业险'),
            ))
            ->add('rentalCar','entity', [
                'label'     => '租赁车辆',
                'class'     => 'AutoManagerBundle:RentalCar',
                'property'  => 'License'
            ])
            ->add('company','entity', [
                'label'     => '投保公司',
                'class'     => 'AutoManagerBundle:Company',
                'query_builder' => function ($repo) {
                    return $repo->createQueryBuilder('c')
                        ->where('c.kind = 2');
                },
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
            'data_class' => 'Auto\Bundle\ManagerBundle\Entity\InsuranceRecord'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'auto_bundle_managerbundle_insurance_record';
    }
}
