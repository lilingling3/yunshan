<?php

namespace Auto\Bundle\ManagerBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class RechargeActivityType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
       $builder
            ->add('name','text',['label'  => '请输入活动名称'])
            // ->add('cashback','entity',[
            //         'label'     => '阶梯一返现（元）',
            //         'class'     => 'AutoManagerBundle:RechargePriceStep',
            //         'property'  => 'cashback'
            //     ])
            // ->add('amount','text',['label'  => '金额'])
            // ->add('weight', 'choice', array(
            //     'label'=>'权重',
            //     'choices'  => array('0' => '权重1', '1' => '权重2', '2' => '权重3', '3' => '权重4', '4' => '权重5', '5' => '权重6'),
            // ))
//            ->add('startTime', 'datetime', array(
//                'label'  => '开始时间',
//                'placeholder' => array(
//                    'year' => 'Year', 'month' => 'Month', 'day' => 'Day',
//                    'hour' => 'Hour', 'minute' => 'Minute', 'second' => 'Second',
//                )
//            ))
//            ->add('endTime', 'datetime', array(
//                'label'  => '结束时间',
//                'placeholder' => array(
//                    'year' => 'Year', 'month' => 'Month', 'day' => 'Day',
//                    'hour' => 'Hour', 'minute' => 'Minute', 'second' => 'Second',
//                )
//            ))
            // ->add('discount', 'choice', array(
            //     'label'=>'充返比例',
            //     'choices'  => array(
            //         '1.1' => '1:1.1','1.2' => '1:1.2','1.3' => '1:1.3','1.4' => '1:1.4','1.5' => '1:1.5',
            //         '1.6' => '1:1.6','1.7' => '1:1.7','1.8' => '1:1.8','1.9' => '1:1.9','2' => '1:2'),
            // ))
        ;
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Auto\Bundle\ManagerBundle\Entity\RechargeActivity'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'auto_bundle_managerbundle_rechargeactivity';
    }
}
