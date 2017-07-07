<?php

namespace Auto\Bundle\ManagerBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class MarketActivityType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title','text',['label'=>'活动名称'])
            ->add('link','text',['label'=>'活动链接','required'  => false])
            ->add('kind', 'choice', array(
                'label' => '活动类型',
                'choices'  => [

                    '1'=>'内部广告',

                    '2'=>'外部广告',
                ]

            ))
            ->add('subject', 'choice', array(
                'label' => '内部广告跳转位置',
                'choices'  => [

                    '1'=>'余额充值页',

                    '2'=>'优惠券页面',

                    '3'=>'认证页面',

                    '4'=>'分享客户端页面',

                    '5'=>'意见反馈页面',

                    '6'=>'推荐建点页面',

                    '7'=>'使用帮助列表页',

                    '8'=>'个人信息业',

                    '9'=>'出行数据页'
                ]

            ))
            ->add('image','hidden',['label'=>'图片','required'  => false])
            ->add('thumb','hidden',['label'=>'缩略图','required'  => false])
        ;
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Auto\Bundle\ManagerBundle\Entity\MarketActivity'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'auto_bundle_managerbundle_marketactivity';
    }
}
