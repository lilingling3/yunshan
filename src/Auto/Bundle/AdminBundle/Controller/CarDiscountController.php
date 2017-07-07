<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 15/7/21
 * Time: 上午11:45
 */

namespace Auto\Bundle\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Auto\Bundle\ManagerBundle\Entity\CarDiscount;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @Route("/carDiscount")
 */
class CarDiscountController extends Controller {

    const PER_PAGE = 20;

    /**
     * @Route("/list/{page}", methods="GET", name="auto_admin_car_discount_list",
     * requirements={"page"="\d+"},
     * defaults={"page"=1})
     * @Template()
     */
    public function listAction(Request $req,$page = 1)
    {
        $carId=$req->query->get('carId');

        $cars=$this->getDoctrine()
            ->getRepository('AutoManagerBundle:Car')
            ->findAll();

        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:CarDiscount')
                ->createQueryBuilder('c')
        ;
        if($carId){
            $qb
                ->andWhere($qb->expr()->eq('c.car', ':car'))
                ->setParameter('car', $carId);
        }

        $lists =
            new Paginator(
                $qb
                    ->select('c')
                    ->orderBy('c.id', 'DESC')
                    ->setMaxResults(self::PER_PAGE)
                    ->setFirstResult(self::PER_PAGE * ($page - 1))
            );


        $total = ceil(count($lists) / self::PER_PAGE);


        return ['lists'=>$lists,'page'=>$page,'total'=>$total,'cars'=>$cars,'carId'=>$carId];
    }

    /**
     * @Route("/new", methods="GET", name="auto_admin_car_discount_new")
     * @Template()
     */
    public function newAction()
    {
        $form = $this->createFormBuilder()
            ->add('car','entity', [
                'label'     => '车型名称',
                'class'     => 'AutoManagerBundle:Car',
                'property'  => 'name'
            ])
            ->add('discount', 'choice', array(
                'label'=>'折扣',
                'choices'  => $this->get("auto_manager.rental_price_helper")->get_discount_list(),
            ))
            ->add('startTime', 'text', ['label'  => '开始时间','data' => ''])
            ->add('endTime', 'text', ['label'  => '结束时间','data' => ''])
            ->setAction($this->generateUrl('auto_admin_car_discount_create'))
            ->setMethod('POST')
            ->getForm();

        return ['form'  => $form->createView()];
    }


    /**
     * @Route("/new", methods="POST", name="auto_admin_car_discount_create")
     * @Template("AutoAdminBundle:CarDiscount:new.html.twig")
     */
    public function createAction(Request $req)
    {
        $carDiscount = new \Auto\Bundle\ManagerBundle\Entity\CarDiscount();

        $form = $this->createFormBuilder()
            ->add('car','entity', [
                'label'     => '车型名称',
                'class'     => 'AutoManagerBundle:Car',
                'property'  => 'name'
            ])
            ->add('discount', 'choice', array(
                'label'=>'折扣',
                'choices'  => $this->get("auto_manager.rental_price_helper")->get_discount_list(),
            ))
            ->add('startTime', 'text', ['label'  => '开始时间','data' => ''])
            ->add('endTime', 'text', ['label'  => '结束时间','data' => ''])
            ->setAction($this->generateUrl('auto_admin_car_discount_create'))
            ->setMethod('POST')
            ->getForm();

        $form->handleRequest($req);

        if ($form->isValid()) {
            $data = $form->getData();
            $carDiscount->setCreatetime(new \DateTime());
            $carDiscount->setStartTime(new \DateTime($data['startTime']));
            $carDiscount->setEndTime(new \DateTime($data['endTime']));
            $carDiscount->setCar($data['car']);
            $carDiscount->setDiscount($data['discount']);

            if($this->get("auto_manager.car_helper")->check_car_discount_overlaps($carDiscount)){
                return $this->render(
                    "AutoAdminBundle:Default:message.html.twig",
                    ['message'=>'该车型折扣时间重复']
                );
            }

            $man = $this->getDoctrine()->getManager();
            $man->persist($carDiscount);
            $man->flush();

            return $this->redirect($this->generateUrl('auto_admin_car_discount_list'));
        }
        return ['form'  => $form->createView()];
    }


    /**
     * @Route("/edit/{id}", methods="GET", name="auto_admin_car_discount_edit",requirements={"id"="\d+"})
     * @Template()
     */
    public function editAction($id)
    {
        $carDiscount = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:CarDiscount')
            ->find($id);

        $form = $this->createFormBuilder()
            ->add('car','entity', [
                'label'     => '车型名称',
                'class'     => 'AutoManagerBundle:Car',
                'property'  => 'name',
                'data' => $carDiscount->getCar()
            ])
            ->add('discount', 'choice', array(
                'label'=>'折扣',
                'choices'  => $this->get("auto_manager.rental_price_helper")->get_discount_list(),
                'data' => $carDiscount->getDiscount()
            ))
            ->add('startTime', 'text', ['label'  => '开始时间','data' => $carDiscount->getStartTime()->format('Y-m-d H:i:s')])
            ->add('endTime', 'text', ['label'  => '结束时间','data' => $carDiscount->getEndTime()->format('Y-m-d H:i:s')])
            ->setAction($this->generateUrl('auto_admin_car_discount_update', ['id' => $carDiscount->getId()]))
            ->setMethod('POST')
            ->getForm();

        return [
            'form'      => $form->createView(),
        ];
    }


    /**
     * @Route("/edit/{id}", methods="POST", name="auto_admin_car_discount_update",requirements={"id"="\d+"})
     * @Template("AutoAdminBundle:CarDiscount:edit.html.twig")
     */
    public function updateAction(Request $req, $id)
    {
        $carDiscount = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:CarDiscount')
            ->find($id);

        $form = $this->createFormBuilder()
            ->add('car','entity', [
                'label'     => '车型名称',
                'class'     => 'AutoManagerBundle:Car',
                'property'  => 'name',
                'data' => $carDiscount->getCar()
            ])
            ->add('discount', 'choice', array(
                'label'=>'折扣',
                'choices'  => $this->get("auto_manager.rental_price_helper")->get_discount_list(),
                'data' => $carDiscount->getDiscount()
            ))
            ->add('startTime', 'text', ['label'  => '开始时间','data' => $carDiscount->getStartTime()->format('Y-m-d H:i:s')])
            ->add('endTime', 'text', ['label'  => '结束时间','data' => $carDiscount->getEndTime()->format('Y-m-d H:i:s')])
            ->setAction($this->generateUrl('auto_admin_car_discount_update', ['id' => $carDiscount->getId()]))
            ->setMethod('POST')
            ->getForm();

        $form->handleRequest($req);
        if ($form->isValid()) {

            $data = $form->getData();
            $carDiscount->setStartTime(new \DateTime($data['startTime']));
            $carDiscount->setEndTime(new \DateTime($data['endTime']));
            $carDiscount->setCar($data['car']);
            $carDiscount->setDiscount($data['discount']);

            if($this->get("auto_manager.car_helper")->check_car_discount_overlaps($carDiscount)){
                return $this->render(
                    "AutoAdminBundle:Default:message.html.twig",
                    ['message'=>'该车型折扣时间重复']
                );
            }
            
            $man = $this->getDoctrine()->getManager();
            $man->persist($carDiscount);
            $man->flush();

            return $this->redirect($this->generateUrl('auto_admin_car_discount_list'));
        }
        return ['form'  => $form->createView()];
    }


    /**
     * @Route("/del/{id}", methods="GET", name="auto_admin_car_discount_delete",requirements={"id"="\d+"})
     */
    public function deleteAction($id)
    {
        $man = $this->getDoctrine()->getManager();
        $carDiscount = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:CarDiscount')
            ->find($id);
        $man->remove($carDiscount);
        $man->flush();

        return $this->redirect($this->generateUrl('auto_admin_car_discount_list'));
    }
}