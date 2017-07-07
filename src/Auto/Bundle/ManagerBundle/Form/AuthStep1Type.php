<?php
/**
 * Created by PhpStorm.
 * User: Tau
 * Date: 2016/12/19
 * Time: 下午10:19
 */

namespace Auto\Bundle\ManagerBundle\Form;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;


class AuthStep1Type extends AbstractType
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

                    '0'=>'审核通过',

                    '1'=>'照片不清晰',

                    '2'=>'缺少驾驶证或副页',

                    '3'=>'驾驶证与副页信息不符',

                    '4'=>'证件过期',

                    '5'=>'与其它证件信息不符',

                    '6'=>'非驾驶证'



                ]

            ))
            ->add('idHandImageAuthError', 'choice', array(
                'choices'  => [

                    '0'=>'审核通过',

                    '1'=>'证件信息被遮挡',

                    '2'=>'面部被遮挡',

                    '3'=>'证件内容不清晰',

                    '4'=>'非本人证件',
                    '5'=>'非手持身份证'

                ]

            ))
            ->add('idImageAuthError', 'choice', array(
                'choices'  => [

                    '0'=>'审核通过',

                    '1'=>'照片不清晰',

                    '2'=>'与其它证件信息不符',

                    '3'=>'非身份证'

                ]

            ));

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
        return 'auto_bundle_managerbundle_auth_step1';
    }
}