<?php

namespace Auto\Bundle\ManagerBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class BusinessDistrictType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name','text',['label'=>'名称'])
            ->add('area','entity', [
                'label'     => '地区',
                'class'     => 'AutoManagerBundle:Area',
                'property'  => 'name'
            ])
            ->add('latitude','text',['label'=>'坐标'])
            ->add('longitude','text',['label'=>'坐标'])
        ;
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Auto\Bundle\ManagerBundle\Entity\BusinessDistrict'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'auto_bundle_managerbundle_businessdistrict';
    }
}
