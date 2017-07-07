<?php

namespace Auto\Bundle\ManagerBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class CarOperateType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name','text',['label'=>'车型'])
            ->add('length','text',['label'=>'长度'])
            ->add('width','text',['label'=>'宽度'])
            ->add('height','text',['label'=>'高度'])
            ->add('driveMileage','text',['label'=>'最大续航'])
            ->add('doors','text',['label'=>'车门数'])
            ->add('seats','text',['label'=>'车座数'])
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
         /*   ->add('images', 'collection', [
                'type'         => 'hidden',
                'label'        =>'图片',
                'allow_add'    => true,
                'allow_delete' => true
            ])*/
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
