<?php

namespace Auto\Bundle\ManagerBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class BlackListType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
//            ->add('createTime','date',['label' => '开始时间'])
//            ->add('endTime','date',['label' => '结束时间'])
            ->add('reason', 'choice', array(
                'label'=>'拉黑种类',
                'choices'  => array('0' => '请选择', '1' => '个人征信不良', '2' => '严重违反用户协议'
                , '3' => '车辆租赁严重过失'),
            ))
            ->add('detail','textarea',['label' => '拉黑原因'])
        ;
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Auto\Bundle\ManagerBundle\Entity\BlackList'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'auto_bundle_managerbundle_blacklist';
    }
}
