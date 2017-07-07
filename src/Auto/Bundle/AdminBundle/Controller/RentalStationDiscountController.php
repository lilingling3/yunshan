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
use Auto\Bundle\ManagerBundle\Entity\RentalStationDiscount;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @Route("/rentalStationDiscount")
 */
class RentalStationDiscountController extends Controller {

    const PER_PAGE = 20;

    /**
     * @Route("/list/{page}", methods="GET", name="auto_admin_rental_station_discount_list",
     * requirements={"page"="\d+"},
     * defaults={"page"=1})
     * @Template()
     */
    public function listAction(Request $req,$page = 1)
    {
        $stationName=$req->query->get('stationName');
        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:RentalStationDiscount')
                ->createQueryBuilder('c')
        ;
        if($stationName){
            $qb
                ->join('c.rentalStation','s')
                ->andWhere($qb->expr()->like('s.name', ':name'))
                ->setParameter('name', "%" . $stationName . "%");
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


        return ['lists'=>$lists,'page'=>$page,'total'=>$total,'stationName'=>$stationName];
    }

    /**
     * @Route("/new", methods="GET", name="auto_admin_rental_station_discount_new")
     * @Template()
     */
    public function newAction()
    {
        $form = $this->createFormBuilder()
            ->add('kind', 'choice', array(
                'label'=>'取还车折扣类型',
                'choices'  => array('1' => '取车折扣', '2' => '还车折扣'),
            ))
            ->add('discount', 'choice', array(
                'label'=>'折扣',
                'choices'  => $this->get("auto_manager.rental_price_helper")->get_discount_list(),
            ))
            ->add('startTime', 'text', ['label'  => '开始时间','data' => ''])
            ->add('endTime', 'text', ['label'  => '结束时间','data' => ''])
            ->setAction($this->generateUrl('auto_admin_rental_station_discount_create'))
            ->setMethod('POST')
            ->getForm();

        return ['form'  => $form->createView()];
    }


    /**
     * @Route("/new", methods="POST", name="auto_admin_rental_station_discount_create")
     * @Template("AutoAdminBundle:RentalStationDiscount:new.html.twig")
     */
    public function createAction(Request $req)
    {
        $rentalStationDiscount = new \Auto\Bundle\ManagerBundle\Entity\RentalStationDiscount();

        $form = $this->createFormBuilder()
            ->add('kind', 'choice', array(
                'label'=>'取还车折扣类型',
                'choices'  => array('1' => '取车折扣', '2' => '还车折扣'),
            ))
            ->add('discount', 'choice', array(
                'label'=>'折扣',
                'choices'  => $this->get("auto_manager.rental_price_helper")->get_discount_list(),
            ))
            ->add('startTime', 'text', ['label'  => '开始时间','data' => ''])
            ->add('endTime', 'text', ['label'  => '结束时间','data' => ''])
            ->setAction($this->generateUrl('auto_admin_rental_station_discount_create'))
            ->setMethod('POST')
            ->getForm();

        $form->handleRequest($req);
        $retalStationId = $req->request->getInt('rental_station');
        $retalStation = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalStation')
            ->find($retalStationId);

        if ($form->isValid() && !empty($retalStation)) {
            $data = $form->getData();
            $rentalStationDiscount->setCreatetime(new \DateTime());
            $rentalStationDiscount->setStartTime(new \DateTime($data['startTime']));
            $rentalStationDiscount->setEndTime(new \DateTime($data['endTime']));
            $rentalStationDiscount->setRentalStation($retalStation);
            $rentalStationDiscount->setKind($data['kind']);
            $rentalStationDiscount->setDiscount($data['discount']);
            if($this->get("auto_manager.station_helper")->check_rental_station_discount_overlaps($rentalStationDiscount)){
                return $this->render(
                    "AutoAdminBundle:Default:message.html.twig",
                    ['message'=>'该租赁点折扣时间重复']
                );
            }
            $man = $this->getDoctrine()->getManager();
            $man->persist($rentalStationDiscount);
            $man->flush();

            return $this->redirect($this->generateUrl('auto_admin_rental_station_discount_list'));
        }
        return ['form'  => $form->createView()];
    }


    /**
     * @Route("/edit/{id}", methods="GET", name="auto_admin_rental_station_discount_edit",requirements={"id"="\d+"})
     * @Template()
     */
    public function editAction($id)
    {
        $rentalStationDiscount = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalStationDiscount')
            ->find($id);

        $form = $this->createFormBuilder()
            ->add('kind', 'choice', array(
                'label'=>'取还车折扣类型',
                'choices'  => array('1' => '取车折扣', '2' => '还车折扣'),
                'data' => $rentalStationDiscount->getKind()
            ))
            ->add('discount', 'choice', array(
                'label'=>'折扣',
                'choices'  => $this->get("auto_manager.rental_price_helper")->get_discount_list(),
                'data' => $rentalStationDiscount->getDiscount()
            ))
            ->add('startTime', 'text', ['label'  => '开始时间','data' => $rentalStationDiscount->getStartTime()->format('Y-m-d H:i:s')])
            ->add('endTime', 'text', ['label'  => '结束时间','data' => $rentalStationDiscount->getEndTime()->format('Y-m-d H:i:s')])
            ->setAction($this->generateUrl('auto_admin_rental_station_discount_update', ['id' => $rentalStationDiscount->getId()]))
            ->setMethod('POST')
            ->getForm();

        return [
            'form'      => $form->createView(),
            'rentalStationDiscount' => $rentalStationDiscount
        ];
    }


    /**
     * @Route("/edit/{id}", methods="POST", name="auto_admin_rental_station_discount_update",requirements={"id"="\d+"})
     * @Template("AutoAdminBundle:RentalStationDiscount:edit.html.twig")
     */
    public function updateAction(Request $req, $id)
    {
        $rentalStationDiscount = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalStationDiscount')
            ->find($id);

        $form = $this->createFormBuilder()
            ->add('kind', 'choice', array(
                'label'=>'取还车折扣类型',
                'choices'  => array('1' => '取车折扣', '2' => '还车折扣'),
                'data' => $rentalStationDiscount->getKind()
            ))
            ->add('discount', 'choice', array(
                'label'=>'折扣',
                'choices'  => $this->get("auto_manager.rental_price_helper")->get_discount_list(),
                'data' => $rentalStationDiscount->getDiscount()
            ))
            ->add('startTime', 'text', ['label'  => '开始时间','data' => $rentalStationDiscount->getStartTime()->format('Y-m-d H:i:s')])
            ->add('endTime', 'text', ['label'  => '结束时间','data' => $rentalStationDiscount->getEndTime()->format('Y-m-d H:i:s')])
            ->setAction($this->generateUrl('auto_admin_rental_station_discount_update', ['id' => $rentalStationDiscount->getId()]))
            ->setMethod('POST')
            ->getForm();

        $form->handleRequest($req);
        if ($form->isValid()) {

            $data = $form->getData();
            $rentalStationDiscount->setStartTime(new \DateTime($data['startTime']));
            $rentalStationDiscount->setEndTime(new \DateTime($data['endTime']));
//            $rentalStationDiscount->setRentalStation($retalStation);
            $rentalStationDiscount->setKind($data['kind']);
            $rentalStationDiscount->setDiscount($data['discount']);
            if($this->get("auto_manager.station_helper")->check_rental_station_discount_overlaps($rentalStationDiscount)){
                return $this->render(
                    "AutoAdminBundle:Default:message.html.twig",
                    ['message'=>'该租赁点折扣时间重复']
                );
            }
            $man = $this->getDoctrine()->getManager();
            $man->persist($rentalStationDiscount);
            $man->flush();

            return $this->redirect($this->generateUrl('auto_admin_rental_station_discount_list'));
        }
        return ['form'  => $form->createView()];
    }


    /**
     * @Route("/del/{id}", methods="GET", name="auto_admin_rental_station_discount_delete",requirements={"id"="\d+"})
     */
    public function deleteAction($id)
    {
        $man = $this->getDoctrine()->getManager();
        $rentalprice = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalStationDiscount')
            ->find($id);
        $man->remove($rentalprice);
        $man->flush();

        return $this->redirect($this->generateUrl('auto_admin_rental_station_discount_list'));
    }
}