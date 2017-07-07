<?php

namespace Auto\Bundle\ManagerBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class RentalCompanyType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name','text',['label'=>'公司名称'])
            ->add('caseNo','text',['label'=>'公司备案号'])
            ->add('enterpriseCode','text',['label'=>'企业代码'])
            ->add('area','entity', [
                'label'     => '地区',
                'class'     => 'AutoManagerBundle:Area',
                'property'  => 'name'
            ])
            ->add('contactName','text',['label'=>'联系人','required' => false])
            ->add('contactMobile','text',['label'=>'联系电话','required' => false])
            ->add('address','text',['label'=>'公司地址'])

        ;
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Auto\Bundle\ManagerBundle\Entity\Company'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'auto_bundle_managerbundle_rental_company';
    }
}
