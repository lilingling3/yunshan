<?php

namespace Auto\Bundle\ManagerBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class CouponActivityType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name','text',['label'=>'活动名称'])
            ->add('count','text',['label'=>'发放个数'])
            ->add('online', 'choice', array(
                'label'=>'上线状态',
                'choices'  => array('1' => '上线', '0' => '下线'),
            ))        ;
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Auto\Bundle\ManagerBundle\Entity\CouponActivity'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'auto_bundle_manager_bundle_coupon_activity';
    }
}
