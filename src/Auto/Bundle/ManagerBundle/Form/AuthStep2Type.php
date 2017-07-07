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


class AuthStep2Type extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder

            ->add('validateError', 'hidden',['data'=>0])
            ->add('IDNumber','text',['label' => '身份证号码','required' => false])
            ->add('IDAddress','text',['label' => '身份证地址','required' => false])
            ->add('licenseAddress','text',['label' => '驾驶证地址','required' => false])
            ->add('licenseNumber','text',['label' => '驾驶证号码','required' => false])
            ->add('licenseValidYear','text',['label' => '驾驶证有效年数','required' => false])
            ->add('licenseProvince','text',['label' => '驾驶证地址省份','required' => false])
            ->add('licenseCity','text',['label' => '驾驶证地址城市','required' => false])
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
            ->add('validateNewResult', 'hidden',[])
            ->add('submitType', 'hidden',['data'=>0])
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
        return 'auto_bundle_managerbundle_auth_step2';
    }
}