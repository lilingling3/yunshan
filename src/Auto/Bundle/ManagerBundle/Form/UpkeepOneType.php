<?php

namespace Auto\Bundle\ManagerBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class UpkeepOneType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('nextMileage','text',['label' => '下次保养里程（km）'])
            ->add('upkeepTime','date', array(
                'label'  => '保养时间',
                'input'  => 'datetime',
                'widget' => 'single_text','format' => 'yyyy-MM-dd'
            ))
            ->add('nextUpkeepTime','date', array(
                'label'  => '下次保养时间',
                'input'  => 'datetime',
                'widget' => 'single_text','format' => 'yyyy-MM-dd'
            ))
            ->add('remark','textarea',['label' => '备注','required' => false])
        ;
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Auto\Bundle\ManagerBundle\Entity\Upkeep'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'auto_bundle_managerbundle_upkeep';
    }
}
