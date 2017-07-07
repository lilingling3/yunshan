<?php

namespace Auto\Bundle\ManagerBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class RegionMaintenanceRecordType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('maintenanceReason','text',['label'  => '事故/故障原因'])
            ->add('maintenanceProject','textarea',['label'  => '维修项目'])
            ->add('downTime', 'datetime', array(
                'label'  => '事故/故障时间',
                'placeholder' => array(
                    'year' => 'Year', 'month' => 'Month', 'day' => 'Day',
                    'hour' => 'Hour', 'minute' => 'Minute', 'second' => 'Second',
                )
            ))
            ->add('maintenanceTime', 'datetime', array(
                'label'  => '维修时间',
                'placeholder' => array(
                    'year' => 'Year', 'month' => 'Month', 'day' => 'Day',
                    'hour' => 'Hour', 'minute' => 'Minute', 'second' => 'Second',
                )
            ))

            ->add('maintenanceAmount','text',['label'  => '定损/维修金额（元）'])
            ->add('kind', 'choice', array(
                'label'=>'维修类型',
                'choices'  => array('0' => '请选择', '1' => '事故维修', '2' => '故障维修'
                ),
            ))
            ->add('status', 'choice', array(
                'label'=>'修理状态',
                'choices'  => array('0' => '请选择', '1' => '已完成', '2' => '未完成'
                ),
            ))
            ->add('company','entity', [
                'label'     => '修理厂名称',
                'class'     => 'AutoManagerBundle:Company',
                'query_builder' => function ($repo) {
                    return $repo->createQueryBuilder('c')
                        ->where('c.kind = 5');
                },
                'property'  => 'name'
            ])
            ->add('images', 'collection', [
                'type'         => 'hidden',
                'label'        =>'图片',
                'allow_add'    => true,
                'allow_delete' => true
            ])
        ;
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Auto\Bundle\ManagerBundle\Entity\MaintenanceRecord'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'auto_bundle_managerbundle_region_maintenance_record';
    }
}
