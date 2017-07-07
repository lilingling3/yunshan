<?php

namespace Auto\Bundle\ManagerBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Doctrine\ORM\EntityRepository;
class CouponType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('kind','entity', [
                'label'     => '优惠券种类',
                'class'     => 'AutoManagerBundle:CouponKind',
                'property'  => 'name'
            ])
            ->add('activity','entity', [
                'label'     => '优惠活动',
                'class'     => 'AutoManagerBundle:CouponActivity',
                'query_builder' => function ($repo) {
                    $qb = $repo->createQueryBuilder('c');
                    return $qb
                        ->where($qb->expr()->isNull('c.order'))
                        ->andWhere($qb->expr()->isNull('c.member'))
                        ->andWhere('c.online = 1');
                },
                'property'  => 'name'
            ])
        ;
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Auto\Bundle\ManagerBundle\Entity\Coupon'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'auto_bundle_managerbundle_coupon';
    }
}
