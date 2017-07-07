<?php

namespace Auto\Bundle\ManagerBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class InspectionType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder

//            ->add('inspectionTime', 'datetime', array(
//                'label'  => '年检时间',
//                'placeholder' => array(
//                    'year' => 'Year', 'month' => 'Month', 'day' => 'Day',
//                    'hour' => 'Hour', 'minute' => 'Minute', 'second' => 'Second',
//                )
//            ))
            ->add('inspectionTime','date', array(
                'label'  => '年检时间',
                'input'  => 'datetime',
                'widget' => 'single_text','format' => 'yyyy-MM-dd'
            ))
            ->add('nextInspectionTime','date', array(
                'label'  => '下次年检时间',
                'input'  => 'datetime',
                'widget' => 'single_text','format' => 'yyyy-MM-dd'
            ))

            ->add('remark','textarea',['label' => '备注','required' => false])
            ->add('rentalCar','entity', [
                'label'     => '租赁车辆',
                'class'     => 'AutoManagerBundle:RentalCar',
                'property'  => 'License'
            ])
        ;
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Auto\Bundle\ManagerBundle\Entity\Inspection'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'auto_bundle_managerbundle_inspection';
    }
}
