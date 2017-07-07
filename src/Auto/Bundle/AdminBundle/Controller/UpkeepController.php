<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 15/7/22
 * Time: 下午4:53
 */

namespace Auto\Bundle\AdminBundle\Controller;

use Auto\Bundle\ManagerBundle\Form\UpkeepType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Auto\Bundle\ManagerBundle\Entity\Upkeep;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @Route("/upkeep")
 */

class UpkeepController extends Controller {

    const PER_PAGE = 20;

    
    /**
     * @Route("/list/{page}", methods="GET", name="auto_admin_upkeep_list",
     * requirements={"page"="\d+"},
     * defaults={"page"=1})
     * @Template()
     */
    public function listAction(Request $req,$page = 1)
    {
        $licensePlace = $req->query->get('licensePlace');
        $licensePlate = $req->query->get('licensePlate');

        $licensePlaces=$this->getDoctrine()
            ->getRepository('AutoManagerBundle:LicensePlace')
            ->findAll();
        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:Upkeep')
                ->createQueryBuilder('i')
                ->select('i')
        ;
        if($licensePlace || $licensePlate){
            $qb = $qb->join('i.rentalCar','r');
            if($licensePlace){
                $qb
                ->andWhere( $qb->expr()->eq('r.licensePlace',':licensePlace') )
                ->setParameter('licensePlace', $licensePlace);
            }
            if($licensePlate){
                $qb
                ->andWhere( $qb->expr()->eq('r.licensePlate',':licensePlate') )
                ->setParameter('licensePlate', $licensePlate);
            }



        }
        $upkeeps =
            new Paginator(
                $qb
                    ->orderBy('i.id', 'DESC')
                    ->setMaxResults(self::PER_PAGE)
                    ->setFirstResult(self::PER_PAGE * ($page - 1))
            );

        $total = ceil(count($upkeeps) / self::PER_PAGE);
        $arrResult = array();
        foreach ($upkeeps as $value) {
            $arrTemp = array();
            $arrTemp['id'] = $value->getId();
            $arrTemp['rentalCar'] = $value->getRentalCar();
//            $mileageCurrent = $this->get('auto_manager.rental_car_helper')->get_rental_car_current_mileage($value->getRentalCar());
            $arrTemp['nextMileage'] = $value->getNextMileage();
            $arrTemp['upkeepTime'] = $value->getUpkeepTime();
            $arrTemp['nextUpkeepTime'] = $value->getNextUpkeepTime();
            $arrTemp['remark'] = $value->getRemark();
//            $arrTemp['upkeepDF'] = floor(($value->getNextUpkeepTime()->getTimestamp()-time())/(3600*24));
//            $arrTemp['mileageDF'] = $value->getNextMileage()-$mileageCurrent;
            $arrTemp['createTime'] = $value->getCreateTime();
            $arrResult[] = $arrTemp;
        }

        return ['upkeeps'=>$arrResult,'page'=>$page,'total'=>$total,'licensePlaces'=>$licensePlaces];
    }

    /**
     * @Route("/new", methods="GET", name="auto_admin_upkeep_new")
     * @Template()
     */
//    public function newAction()
//    {
//        $form = $this->createForm(new UpkeepType(), null, [
//            'action' => $this->generateUrl('auto_admin_upkeep_create'),
//            'method' => 'POST'
//        ]);
//
//        return ['form'  => $form->createView()];
//    }

    /**
     * @Route("/new", methods="POST", name="auto_admin_upkeep_create")
     * @Template("AutoAdminBundle:Upkeep:new.html.twig")
     */
//    public function createAction(Request $req)
//    {
//        $upkeep = new \Auto\Bundle\ManagerBundle\Entity\Upkeep();
//
//        $form = $this->createForm(new UpkeepType(), $upkeep, [
//            'action' => $this->generateUrl('auto_admin_upkeep_create'),
//            'method' => 'POST'
//        ]);
//
//        $form->handleRequest($req);
//
//        if ($form->isValid()) {
//
//            $man = $this->getDoctrine()->getManager();
//            $man->persist($upkeep);
//            $man->flush();
//
//            return $this->redirect($this->generateUrl('auto_admin_upkeep_list'));
//        }
//        return ['form'  => $form->createView()];
//    }
    /**
     * @Route("/edit/{id}", methods="GET", name="auto_admin_upkeep_edit",requirements={"id"="\d+"})
     * @Template()
     */
    public function editAction($id)
    {
        $upkeep = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Upkeep')
            ->find($id);
        $mileageCurrent = $this->get('auto_manager.rental_car_helper')->get_rental_car_current_mileage($upkeep->getRentalCar());
        $form = $this->createForm(new UpkeepType(), $upkeep, [
            'action' => $this->generateUrl('auto_admin_upkeep_update', ['id' => $upkeep->getId()]),
            'method' => 'POST'
        ]);

        return [
            'form'  => $form->createView(),'mileageCurrent'=>$mileageCurrent
        ];
    }


    /**
     * @Route("/edit{id}", methods="POST", name="auto_admin_upkeep_update",requirements={"id"="\d+"})
     * @Template("AutoAdminBundle:Car:edit.html.twig")
     */
    public function updateAction(Request $req, $id)
    {
        $upkeep = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Upkeep')
            ->find($id);
        $form = $this->createForm(new UpkeepType(), $upkeep, [
            'action' => $this->generateUrl('auto_admin_upkeep_update', ['id' => $upkeep->getId()]),
            'method' => 'POST'
        ]);

        $form->handleRequest($req);
        if ($form->isValid()) {

            $man = $this->getDoctrine()->getManager();
            $man->persist($upkeep);
            $man->flush();

            return $this->redirect($this->generateUrl('auto_admin_upkeep_list'));
        }
        return ['form'  => $form->createView()];
    }


    /**
     * @Route("/del/{id}", methods="GET", name="auto_admin_upkeep_delete",requirements={"id"="\d+"})
     */
    public function deleteAction($id)
    {
        $man = $this->getDoctrine()->getManager();
        $upkeep = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Upkeep')
            ->find($id);
        $man->remove($upkeep);
        $man->flush();

        return $this->redirect($this->generateUrl('auto_admin_upkeep_list'));
    }

    /**
     * @Route("/add/rentalcar/{id}", methods="GET", name="auto_admin_upkeep_add_by_rentalcar",
     * requirements={"id"="\d+"})
     * @Template("AutoAdminBundle:Upkeep:new.html.twig")
     */
    public function addByRentalCarAction($id)
    {
        $rentalcar = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->find($id);

        $upkeep = new \Auto\Bundle\ManagerBundle\Entity\Upkeep();

        $upkeep->setRentalCar($rentalcar);

        $form = $this->createForm(new UpkeepType(), $upkeep, [
            'action' => $this->generateUrl('auto_admin_upkeep_create'),
            'method' => 'POST'
        ]);

        return ['form'  => $form->createView(),'rentalcar' => $rentalcar];
    }



}