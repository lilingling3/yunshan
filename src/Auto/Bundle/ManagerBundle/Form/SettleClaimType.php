<?php

namespace Auto\Bundle\ManagerBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class SettleClaimType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('claimLicensePlate','text',['label' => '理赔车车牌号'])
            ->add('downReason','textarea',['label' => '事故原因'])
            ->add('claimAmount','text',['label' => '理赔金额(元)'])
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
            'data_class' => 'Auto\Bundle\ManagerBundle\Entity\SettleClaim'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'auto_bundle_managerbundle_settleclaim';
    }
}
