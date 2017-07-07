<?php

namespace Auto\Bundle\ManagerBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class AuthMemberType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('licenseAuthError', 'choice', array(
                'choices'  => [
                    '0'=>'通过',

                    '1'=>'证件照片内容不清晰',

                    '2'=>'缺少驾驶证或驾驶证副页',

                    '3'=>'驾驶证领取时间未满1年',

                    '4'=>'驾驶证已过期',

                    '5'=>'驾驶证与驾驶证副页信息不符',

                    '6'=>'证件信息与交管系统信息不符',

                     '7'=>'电话无人接听',

                    '8'=>'不是本人',

                     '9'=>'无法提供身份证信息'
                ]

            ))
            ->add('IDNumber','text',['label' => '驾照号码','required' => false])

            ->add('documentNumber','text',['label'  => '档案号','required' => false])

            ->add('licenseStartDate','date', array(
                'label'  => '证件有效开始日期',
                'input'  => 'datetime',
                'required' => false,
                'widget' => 'single_text','format' => 'yyyy-MM-dd'
            ))
            ->add('licenseEndDate','date', array(
                'label'  => '证件有效结束日期',
                'input'  => 'datetime',
                'required' => false,
                'widget' => 'single_text','format' => 'yyyy-MM-dd'
            ))
        ;
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Auto\Bundle\ManagerBundle\Entity\AuthMember'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'auto_bundle_managerbundle_authmember';
    }
}
