<?php

namespace Auto\Bundle\ManagerBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class PartnerType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name','text')
            ->add('operatorIds','text')
            ->add('operateLimit','text')
            ->add('visibleCars','text')
            ->add('discount', 'choice', array(
                'label'=>'折扣',
                'choices'  => [
                    '1' => '不打折',
                    '0.95' => '95折',
                    '0.9' => '9折',
                    '0.85' => '85折',
                    '0.8' => '8折',
                    '0.75' => '75折',
                    '0.7' => '7折',
                    '0.65' => '65折',
                    '0.6' => '6折',
                    '0.55' => '55折',
                    '0.5' => '5折'
                ],
            ))
            ->add('status', 'choice', array(
                'label'=>'状态',
                'choices'  => array('0' => '关闭', '1' => '开启'),
            ))
        ;
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Auto\Bundle\ManagerBundle\Entity\Partner'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'auto_bundle_managerbundle_partner';
    }
    /**
     * @return string
     */
    public function getOperatorIds()
    {
        return 'auto_bundle_managerbundle_partner';
    }
    /**
     * @return string
     */
    public function getVisibleCars()
    {
        return 'auto_bundle_managerbundle_partner';
    }
    /**
     * @return string
     */
    public function getOperateLimit()
    {
        return 'auto_bundle_managerbundle_partner';
    }
}
