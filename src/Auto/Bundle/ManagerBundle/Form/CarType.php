<?php

namespace Auto\Bundle\ManagerBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class CarType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name','text',['label'=>'车型'])
            ->add('image','hidden',['label'=>'图片'])
            ->add('length','text',['label'=>'长'])
            ->add('width','text',['label'=>'宽'])
            ->add('driveMileage','text',['label'=>'续航里程'])
            ->add('autoOfflineMileage','text',['label'=>'自动下线里程'])
            ->add('height','text',['label'=>'高'])
            ->add('doors','text',['label'=>'车门数'])
            ->add('seats','text',['label'=>'车座数'])
            ->add('battery','text',['label'=>'电池容量'])
            ->add('brand','text',['label'=>'品牌'])

            //->add('airbags','text',['label'=>'气囊数'])
            //->add('insuranceAmount','text',['label'=>'不计免赔保险'])
            ->add('bodyType','entity', [
                'label'     => '车身结构',
                'class'     => 'AutoManagerBundle:BodyType',
                'property'  => 'name'
    ])
            ->add('level','entity', [
                'label'     => '级别',
                'class'     => 'AutoManagerBundle:CarLevel',
                'property'  => 'name'
            ])
             ->add('startType', 'choice', array(
                'label' => '启动方式',
                'choices'  => [

                    '1'=>'钥匙启动',

                    '2'=>'一键启动',
                ]

            ))
        ;
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Auto\Bundle\ManagerBundle\Entity\Car'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'auto_bundle_managerbundle_car';
    }
}
