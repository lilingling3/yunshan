<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 15/8/5
 * Time: 上午11:05
 */

namespace Auto\Bundle\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Auto\Bundle\ManagerBundle\Entity\RentalStation;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Auto\Bundle\ManagerBundle\Form\RentalStationType;

/**
 * @Route("/regionrentalstation")
 */

class RegionRentalStationController extends Controller {

    const PER_PAGE = 20;

    /**
     * @Route("/list/{page}", methods="GET", name="auto_admin_region_rental_station_list",
     * requirements={"page"="\d+"},
     * defaults={"page"=1})
     * @Template()
     */
    public function listAction($page = 1)
    {
        $region = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Region')
            ->findOneBy(['member'=>$this->getUser()]);
        $areaIds = array();
        if(!empty($region)){
            $oAreas = $region->getAreas();
            foreach ($oAreas as $oArea) {
                $child1 = $oArea->getChildren()->toArray();
                if(empty($child1)){
                    $areaIds[] = $oArea->getId();
                }else{
                    foreach ($child1 as $c1) {
                        $child2 = $c1->getChildren()->toArray();
                        if(empty($child2)){
                            $areaIds[] = $c1->getId();
                        }else{
                            foreach ($child2 as $c2) {
                                $areaIds[] = $c2->getId();
                            }
                        }
                    }
                }
            }
        }
        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:RentalStation')
                ->createQueryBuilder('s')
        ;
        $rentalStations =
            new Paginator(
                $qb
                    ->select('s')
                    ->where($qb->expr()->in('s.area',$areaIds))
                    ->orderBy('s.id', 'DESC')
                    ->setMaxResults(self::PER_PAGE)
                    ->setFirstResult(self::PER_PAGE * ($page - 1))
            );

        $total = ceil(count($rentalStations) / self::PER_PAGE);

        return ['rentalStations'=>$rentalStations,'page'=>$page,'total'=>$total];
    }


    /**
     * @Route("/new", methods="GET", name="auto_admin_region_rental_station_new")
     * @Template()
     */
    public function newAction()
    {
        $form = $this->createForm(new RentalStationType(), null, [
            'action' => $this->generateUrl('auto_admin_region_rental_station_create'),
            'method' => 'POST'
        ]);

        return ['form'  => $form->createView()];
    }

    /**
     * @Route("/new", methods="POST", name="auto_admin_region_rental_station_create")
     * @Template("AutoAdminBundle:RentalStation:new.html.twig")
     */
    public function createAction(Request $req)
    {
        $rentalStation = new \Auto\Bundle\ManagerBundle\Entity\RentalStation();

        $form = $this->createForm(new RentalStationType(), $rentalStation, [
            'action' => $this->generateUrl('auto_admin_region_rental_station_create'),
            'method' => 'POST'
        ]);

        $form->handleRequest($req);

        if ($form->isValid()) {

            $man = $this->getDoctrine()->getManager();
            $man->persist($rentalStation);
            $man->flush();

            return $this->redirect($this->generateUrl('auto_admin_region_rental_station_list'));
        }

        return ['form'  => $form->createView()];
    }

    /**
     * @Route("/edit/{id}", methods="GET", name="auto_admin_region_rental_station_edit",requirements={"id"="\d+"})
     * @Template()
     */
    public function editAction($id)
    {
        $station = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalStation')
            ->find($id);

        $form = $this->createForm(new RentalStationType(), $station, [
            'action' => $this->generateUrl('auto_admin_region_rental_station_update',['id' => $station->getId()]),
            'method' => 'POST'
        ]);

        return [
            'form'  => $form->createView()
        ];
    }


    /**
     * @Route("/edit{id}", methods="POST", name="auto_admin_region_rental_station_update",requirements={"id"="\d+"})
     * @Template("AutoAdminBundle:Car:edit.html.twig")
     */
    public function updateAction(Request $req, $id)
    {
        $rentalStation = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalStation')
            ->find($id);
        $form = $this->createForm(new RentalStationType(), $rentalStation, [
            'action' => $this->generateUrl('auto_admin_region_rental_station_update', ['id' => $rentalStation->getId()]),
            'method' => 'POST'
        ]);

        $form->handleRequest($req);
        if ($form->isValid()) {

            $man = $this->getDoctrine()->getManager();
            $man->persist($rentalStation);
            $man->flush();

            return $this->redirect($this->generateUrl('auto_admin_region_rental_station_list'));
        }
        return ['form'  => $form->createView()];
    }



    /**
     * @Route("/add/operator/{id}", methods="GET", name="auto_admin_region_add_station_operator")
     * @Template()
     */
    public function addOperatorAction($id)
    {
        $query = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Operator')
            ->createQueryBuilder('o');

       $operators = $query
            ->select('o')
            ->innerJoin('o.stations', 's', 'WITH', 's.id = :sid')
            ->where('s.id = :sid')
            ->setParameter('sid', $id)
            ->getQuery()
            ->getResult();

        $form = $this
            ->createFormBuilder()
            ->add('mobile', 'text')
            ->setAction($this->generateUrl('auto_admin_create_station_operator',['id'=>$id]))
            ->setMethod('post')
            ->getForm();

        return ['form'  => $form->createView(),'operators'=>$operators];
    }


}