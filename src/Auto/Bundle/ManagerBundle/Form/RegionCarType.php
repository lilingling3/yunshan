<?php

namespace Auto\Bundle\ManagerBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class RegionCarType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
    	if ($options['action'] == '/admin/regioncar/new'){
        	$builder
            ->add('licensePlate','text',['label' => '车牌号'])
            ->add('boxId','text',['label' => '设备号','required' => false])
            ->add('engineNumber','text',['label' => '发动机号'])
            ->add('chassisNumber','text',['label' => '车架号'])
            ->add('car','entity', [
                'label'     => '车型',
                'class'     => 'AutoManagerBundle:Car',
                'property'  => 'name'
            ])
            ->add('company','entity', [
                'label'     => '归属公司',
                'class'     => 'AutoManagerBundle:Company',
            	'query_builder' => function ($repo) {
            		return $repo->createQueryBuilder('c')
            		->where('c.kind = 3');
            	},
                'property'  => 'name'
            ])
            ->add('deviceCompany','entity', [
                'label'     => '设备公司',
                'class'     => 'AutoManagerBundle:Company',
                'query_builder' => function ($repo) {
                    return $repo->createQueryBuilder('c')
                        ->where('c.kind = 1');
                },
                'property'  => 'name'
            ])
            ->add('licensePlace','entity', [
                'label'     => '车牌归属地',
                'class'     => 'AutoManagerBundle:LicensePlace',
                'property'  => 'name'
            ])
            ->add('color','entity', [
            'label'     => '颜色',
            'class'     => 'AutoManagerBundle:Color',
            'property'  => 'name'
            ])
           
            ->add('operationKind', 'choice', array(
                'label'=>'车辆使用性质',
                'choices'  => array(''=>'请选择','1' => '营运', '2' => '非营运', '3' => '租赁'),
            ))
            ->add('images', 'collection', [
                'type'         => 'hidden',
                'label'        =>'图片',
                'allow_add'    => true,
                'allow_delete' => true
            ]);
    	}else{
        	$builder
            ->add('licensePlate','text',['label' => '车牌号'])
            ->add('boxId','text',['label' => '设备号','required' => false])
            ->add('engineNumber','text',['label' => '发动机号'])
            ->add('chassisNumber','text',['label' => '车架号'])
            ->add('car','entity', [
                'label'     => '车型',
                'class'     => 'AutoManagerBundle:Car',
                'property'  => 'name'
            ])
            ->add('company','entity', [
                'label'     => '归属公司',
                'class'     => 'AutoManagerBundle:Company',
            	'query_builder' => function ($repo) {
            		return $repo->createQueryBuilder('c')
            		->where('c.kind = 3');
            	},
                'property'  => 'name'
            ])
            ->add('deviceCompany','entity', [
                'label'     => '设备公司',
                'class'     => 'AutoManagerBundle:Company',
                'query_builder' => function ($repo) {
                    return $repo->createQueryBuilder('c')
                        ->where('c.kind = 1');
                },
                'property'  => 'name'
            ])
            ->add('licensePlace','entity', [
                'label'     => '车牌归属地',
                'class'     => 'AutoManagerBundle:LicensePlace',
                'property'  => 'name'
            ])
            ->add('color','entity', [
            'label'     => '颜色',
            'class'     => 'AutoManagerBundle:Color',
            'property'  => 'name'
            ])
           
            ->add('operationKind', 'choice', array(
                'label'=>'车辆使用性质',
                'choices'  => array(''=>'请选择','1' => '营运', '2' => '非营运', '3' => '租赁'),
            ))
            ->add('images', 'collection', [
                'type'         => 'hidden',
                'label'        =>'图片',
                'allow_add'    => true,
                'allow_delete' => true
            ]);
    	}
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Auto\Bundle\ManagerBundle\Entity\RentalCar'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'auto_bundle_managerbundle_regioncar';
    }
}
