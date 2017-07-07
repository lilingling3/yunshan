<?php

namespace Auto\Bundle\ManagerBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class IllegalRecordType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            /*->add('illegal')
            ->add('handleTime')
            ->add('illegalTime')
            ->add('createTime')
            ->add('illegalScore')
            ->add('illegalPlace')
            ->add('illegalAmount')
            ->add('delayAmount')
            ->add('counterFee')
            ->add('order')
            ->add('rentalCar')
            ->add('agent')*/
            ->add('agentAmount','text',['label'  => '金额（元）'])
            ->add('remark','textarea',['label'  => '备注'])

        ;
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Auto\Bundle\ManagerBundle\Entity\IllegalRecord'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'auto_bundle_managerbundle_illegalrecord';
    }
}
