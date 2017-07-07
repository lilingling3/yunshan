<?php

namespace Auto\Bundle\ManagerBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class AuthMemberNewType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('licenseImageAuthError', 'choice', array(
                'choices'  => [

                    '0'=>'驾驶证审核通过',

                    '1'=>'驾驶证照片不清晰',

                    '2'=>'缺少驾驶证或副页',

                    '3'=>'驾驶证与副页信息不符',

                    '4'=>'驾驶证已过期',

                    '5'=>'驾驶证与其他证件信息不符',



                ]

            ))
            ->add('idHandImageAuthError', 'choice', array(
                'choices'  => [

                    '0'=>'手持身份证审核通过',

                    '1'=>'手持身份证，身份证信息被遮挡',

                    '2'=>'手持身份证，面部被遮挡',

                    '3'=>'手持身份证，身份证内容不清晰',

                    '4'=>'手持身份证非本人证件',

                ]

            ))
            ->add('idImageAuthError', 'choice', array(
                'choices'  => [

                    '0'=>'身份证审核通过',

                    '1'=>'身份证照片不清晰',

                    '2'=>'身份证与其他证件信息不符',

                ]

            ))
            ->add('mobileCallError', 'choice', array(
                'label' => '电话回访审核',
                'choices'  => [

                    '0'=>'电话回访审核通过',

                    '1'=>'电话无人接听',

                    '2'=>'不是本人',

                    '3'=>'无法提供身份证信息',

                ]

            ))
            ->add('validateError', 'choice', array(
                'choices'  => [

                    '0'=>'第三方审核通过',

                    '1'=>'认证信息未通过社会征信系统审核,请确定证件的合法性.(驾照、身份证、诚信)',

                ]

            ))

            ->add('IDNumber','text',['label' => '驾照号码','required' => false])

            ->add('validateResult','text',['label' => '第三方验证结果','required' => false])

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
