<?php

namespace Auto\Bundle\ManagerBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class RentalPriceType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('price','text',['label'  => '价格'])
            ->add('name','text',['label'   => '名称'])
            ->add('car','entity', [
                'label'     => '租赁车辆',
                'class'     => 'AutoManagerBundle:Car',
                'property'  => 'name'
            ])
            ->add('area','entity', [
                'label'     => '租赁车辆',
                'class'     => 'AutoManagerBundle:Area',
                'property'  => 'name'
            ])

            ->add('weight', 'choice', array(
                'label'=>'权重',
                'choices'  => array('0' => '一般', '1' => '中等', '2' => '重要'),
            ))
            ->add('maxHour','text',['label' => '最大时间(小时)','required' => false])
            ->add('minHour','text',['label' => '最小时间(小时)'])

            ->add('startTime', 'date', array(
                'label'  => '开始时间',
                'input'  => 'datetime',
                'widget' => 'single_text','format' => 'yyyy-MM-dd',
                'required'=> false
            ))
            ->add('endTime', 'date', array(
                'label'  => '结束时间',
                'input'  => 'datetime',
                'widget' => 'single_text','format' => 'yyyy-MM-dd',
                'required'=> false
            ))        ;
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Auto\Bundle\ManagerBundle\Entity\RentalPrice'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'auto_bundle_managerbundle_rentalprice';
    }
}
