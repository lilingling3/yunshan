<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 15/7/22
 * Time: 下午4:53
 */

namespace Auto\Bundle\AdminBundle\Controller;

use Auto\Bundle\ManagerBundle\Form\InspectionType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Auto\Bundle\ManagerBundle\Entity\Inspection;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @Route("/inspection")
 */

class InspectionController extends Controller {

    const PER_PAGE = 20;


    /**
     * @Route("/list/{page}", methods="GET", name="auto_admin_inspection_list",
     * requirements={"page"="\d+"},
     * defaults={"page"=1})
     * @Template()
     */
    public function listAction(Request $req,$page = 1)
    {

        $licensePlace = $req->query->get('licensePlace');
        $licensePlate = $req->query->get('licensePlate');
        $startTime = $req->query->get('startTime');
        $endTime = $req->query->get('endTime');

        $licensePlaces=$this->getDoctrine()
            ->getRepository('AutoManagerBundle:LicensePlace')
            ->findAll();

        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:Inspection')
                ->createQueryBuilder('i')
                ->select('i')
        ;

        if($licensePlace || $licensePlate) {
            $qb = $qb->join('i.rentalCar', 'r');
            if ($licensePlace) {
                $qb
                    ->andWhere($qb->expr()->eq('r.licensePlace', ':licensePlace'))
                    ->setParameter('licensePlace', $licensePlace);
            }
            if ($licensePlate) {
                $qb
                    ->andWhere($qb->expr()->eq('r.licensePlate', ':licensePlate'))
                    ->setParameter('licensePlate', $licensePlate);
            }
        }

        if($startTime){
        $qb
            ->andWhere($qb->expr()->gte('i.nextInspectionTime', ':start_time'))
            ->setParameter('start_time', $startTime)
            ;

         }

        if($endTime){
            $qb
                ->andWhere($qb->expr()->lte('i.nextInspectionTime', ':end_time'))
                ->setParameter('end_time', $endTime)
            ;

        }


        $inspections =
            new Paginator(
                $qb
                    ->orderBy('i.id', 'DESC')
                    ->setMaxResults(self::PER_PAGE)
                    ->setFirstResult(self::PER_PAGE * ($page - 1))
            );

        $total = ceil(count($inspections) / self::PER_PAGE);
        $arrResult = array();
        foreach ($inspections as $value) {
            $arrTemp = array();
            $arrTemp['id'] = $value->getId();
            $arrTemp['rentalCar'] = $value->getRentalCar();
            $arrTemp['inspectionTime'] = $value->getInspectionTime();
            $arrTemp['createTime'] = $value->getCreateTime();
            $arrTemp['nextInspectionTime'] = $value->getNextInspectionTime();
            $arrTemp['remark'] = $value->getRemark();
            $arrTemp['inspectionYear'] = $value->getInspectionYear();
            if($value->getNextInspectionTime()->getTimestamp() >= strtotime(date('Y-m-d'))){
                $arrTemp['overdueStatus'] = '否';
                $arrTemp['overdueDay'] = '-';
            }else{
                $arrTemp['overdueStatus'] = '是';
                $arrTemp['overdueDay'] = floor((time()-$value->getNextInspectionTime()->getTimestamp())/(3600*24));
            }

            $arrResult[] = $arrTemp;
        }

        return ['inspections'=>$arrResult,'page'=>$page,'total'=>$total,'licensePlaces'=>$licensePlaces];
    }

    /**
     * @Route("/new", methods="GET", name="auto_admin_inspection_new")
     * @Template()
     */
    public function newAction()
    {
        $form = $this->createForm(new InspectionType(), null, [
            'action' => $this->generateUrl('auto_admin_inspection_create'),
            'method' => 'POST'
        ]);

        return ['form'  => $form->createView()];
    }

    /**
     * @Route("/new", methods="POST", name="auto_admin_inspection_create")
     * @Template("AutoAdminBundle:Inspection:new.html.twig")
     */
    public function createAction(Request $req)
    {
        $inspection = new \Auto\Bundle\ManagerBundle\Entity\Inspection();

        $form = $this->createForm(new InspectionType(), $inspection, [
            'action' => $this->generateUrl('auto_admin_inspection_create'),
            'method' => 'POST'
        ]);

        $form->handleRequest($req);

        if ($form->isValid()) {

            $man = $this->getDoctrine()->getManager();
            $man->persist($inspection);
            $man->flush();

            return $this->redirect($this->generateUrl('auto_admin_inspection_list'));
        }
        return ['form'  => $form->createView()];
    }
    /**
     * @Route("/edit/{id}", methods="GET", name="auto_admin_inspection_edit",requirements={"id"="\d+"})
     * @Template()
     */
    public function editAction($id)
    {
        $inspection = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Inspection')
            ->find($id);
        $form = $this->createForm(new InspectionType(), $inspection, [
            'action' => $this->generateUrl('auto_admin_inspection_update', ['id' => $inspection->getId()]),
            'method' => 'POST'
        ]);

        return [
            'form'  => $form->createView()
        ];
    }


    /**
     * @Route("/edit{id}", methods="POST", name="auto_admin_inspection_update",requirements={"id"="\d+"})
     * @Template("AutoAdminBundle:Car:edit.html.twig")
     */
    public function updateAction(Request $req, $id)
    {
        $inspection = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Inspection')
            ->find($id);
        $form = $this->createForm(new InspectionType(), $inspection, [
            'action' => $this->generateUrl('auto_admin_inspection_update', ['id' => $inspection->getId()]),
            'method' => 'POST'
        ]);

        $form->handleRequest($req);
        if ($form->isValid()) {

            $man = $this->getDoctrine()->getManager();
            $man->persist($inspection);
            $man->flush();

            return $this->redirect($this->generateUrl('auto_admin_inspection_list'));
        }
        return ['form'  => $form->createView()];
    }


    /**
     * @Route("/del/{id}", methods="GET", name="auto_admin_inspection_delete",requirements={"id"="\d+"})
     */
    public function deleteAction($id)
    {
        $man = $this->getDoctrine()->getManager();
        $inspection = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Inspection')
            ->find($id);
        $man->remove($inspection);
        $man->flush();

        return $this->redirect($this->generateUrl('auto_admin_inspection_list'));
    }

    /**
     * @Route("/add/rentalcar/{id}", methods="GET", name="auto_admin_inspection_add_by_rentalcar",
     * requirements={"id"="\d+"})
     * @Template("AutoAdminBundle:Inspection:new.html.twig")
     */
    public function addByRentalCarAction($id)
    {
        $rentalcar = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->find($id);

        $inspection = new \Auto\Bundle\ManagerBundle\Entity\Inspection();

        $inspection->setRentalCar($rentalcar);

        $form = $this->createForm(new InspectionType(), $inspection, [
            'action' => $this->generateUrl('auto_admin_inspection_create'),
            'method' => 'POST'
        ]);

        return ['form'  => $form->createView(),'rentalcar' => $rentalcar];
    }

    /**
     * @Route("/list/rentalcar/{id}", methods="GET", name="auto_admin_inspection_list_by_rentalcar",
     * requirements={"id"="\d+"})
     * @Template("AutoAdminBundle:Inspection:renderList.html.twig")
     */
    public function listByRentalCarAction($id)
    {
        $rentalcar = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->find($id);

        $inspections = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Inspection')
            ->findBy(['rentalCar'=>$rentalcar],['id'=>'desc']);

        return ['inspections'=>$inspections,'rentalcar' => $rentalcar];
    }


}