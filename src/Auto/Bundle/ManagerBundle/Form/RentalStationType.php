<?php

namespace Auto\Bundle\ManagerBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class RentalStationType extends AbstractType
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
            ->add('businessDistrict','entity', [
                'label'     => '商业圈',
                'class'     => 'AutoManagerBundle:BusinessDistrict',
                'property'  => 'name'
            ])
            ->add('parkingSpaceTotal','text',['label'=>'停车位总数'])
            ->add('usableParkingSpace','text',['label'=>'可停车位总数'])
            ->add('contactMobile','text',['label'=>'联系电话'])
            ->add('backType','choice',array(
                'label'=>'还车类型',
                'choices'  => array('600' => '原地还车', '601' => '异地还车')
            ))
            ->add('street','text',['label'=>'具体位置'])
            ->add('latitude','text',['label'=>'坐标'])
            ->add('longitude','text',['label'=>'坐标'])
            ->add('images', 'collection', [
                'type'         => 'hidden',
                'label'        =>'图片',
                'allow_add'    => true,
                'allow_delete' => true
            ])
            ->add('online', 'choice', array(
                'label'=>'上线状态',
                'choices'  => array('1' => '上线', '0' => '下线'),
            ))
            ->add('company','entity', [
                'label'     => '租赁公司',
                'class'     => 'AutoManagerBundle:Company',
                'query_builder' => function ($repo) {
                    return $repo->createQueryBuilder('c')
                        ->where('c.kind = 6');
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
            'data_class' => 'Auto\Bundle\ManagerBundle\Entity\RentalStation'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'auto_bundle_managerbundle_rentalstation';
    }
}
