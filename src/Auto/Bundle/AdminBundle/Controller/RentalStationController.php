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
use Symfony\Component\HttpFoundation\JsonResponse;
/**
 * @Route("/rentalstation")
 */

class RentalStationController extends Controller {

    const PER_PAGE = 20;

    /**
     * @Route("/list/{page}", methods="GET", name="auto_admin_rental_station_list",
     * requirements={"page"="\d+"},
     * defaults={"page"=1})
     * @Template()
     */
    public function listAction(Request $req,$page = 1)
    {
        $stationName = $req->query->get('stationName');
        $partnerCode = $req->query->get('partnerCode');
        $backType = $req->query->get('backType');
        $province = empty($req->query->get('province')) ? '':$req->query->get('province');
        $city = empty($req->query->getInt('city')) ? '': $req->query->getInt('city');

        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:RentalStation')
                ->createQueryBuilder('s')
        ;

        $qb =
            $qb
                ->select('s');

        if($partnerCode)
        {
            $stationIds = $this->get('auto_manager.partner_helper')->getStationIdsByPartnerCode($partnerCode);
            $qb =
                $qb
                    ->andWhere('s.id in (:id)')
                    ->setParameter('id', $stationIds)
            ;
        }

        if($backType){
            $qb =
                $qb
                    ->andWhere( $qb->expr()->eq('s.backType',':backType') )
                    ->setParameter('backType', $backType)
            ;
        }
        if($stationName) {
            $qb =
                $qb
                    ->andWhere($qb->expr()->like('s.name', ':name'))
                    ->setParameter('name', "%" . $stationName . "%");
        }

        if ($province !=='' && $province !=='请选择') {

            $distinctList =  $this->get('auto_manager.area_helper')->getDistinctList($province,$city);

            $qb
                ->andWhere($qb->expr()->in('s.area', ':area'))
                ->setParameter('area', $distinctList)
            ;
        }

        $rentalStations =
            new Paginator(
                $qb
                    ->orderBy('s.id', 'DESC')
                    ->setMaxResults(self::PER_PAGE)
                    ->setFirstResult(self::PER_PAGE * ($page - 1))
            );

        $total = ceil(count($rentalStations) / self::PER_PAGE);

        $re = [
            'rentalStations' => $rentalStations, 'page' => $page, 'total' => $total,
            'stationName' => $stationName, 'backType' => $backType, 'province' => $province,
            'city' => $city, 'partnerCode' => $partnerCode
        ];
        return $re;
    }


    /**
     * @Route("/new", methods="GET", name="auto_admin_rental_station_new")
     * @Template()
     */
    public function newAction()
    {
        $form = $this->createForm(new RentalStationType(), null, [
            'action' => $this->generateUrl('auto_admin_rental_station_create'),
            'method' => 'POST'
        ]);

        return ['form'  => $form->createView()];
    }

    /**
     * @Route("/new", methods="POST", name="auto_admin_rental_station_create")
     * @Template("AutoAdminBundle:RentalStation:new.html.twig")
     */
    public function createAction(Request $req)
    {
        $rentalStation = new \Auto\Bundle\ManagerBundle\Entity\RentalStation();

        $form = $this->createForm(new RentalStationType(), $rentalStation, [
            'action' => $this->generateUrl('auto_admin_rental_station_create'),
            'method' => 'POST'
        ]);

        $form->handleRequest($req);

        if ($form->isValid()) {

            $man = $this->getDoctrine()->getManager();
            $man->persist($rentalStation);
            $man->flush();

            return $this->redirect($this->generateUrl('auto_admin_rental_station_list'));
        }

        return ['form'  => $form->createView()];
    }

    /**
     * @Route("/show/{id}", methods="GET", name="auto_admin_rental_station_show",requirements={"id"="\d+"})
     * @Template()
     */
    public function showAction($id)
    {
        $station = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalStation')
            ->find($id);
//        dump($station);
//        dump($station->getImages());die;
        return ['station'  => $station ];
    }

    /**
     * @Route("/edit/{id}", methods="GET", name="auto_admin_rental_station_edit",requirements={"id"="\d+"})
     * @Template()
     */
    public function editAction($id)
    {
        $station = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalStation')
            ->find($id);

        $form = $this->createForm(new RentalStationType(), $station, [
            'action' => $this->generateUrl('auto_admin_rental_station_update',['id' => $station->getId()]),
            'method' => 'POST'
        ]);

        return [
            'form'  => $form->createView()
        ];
    }


    /**
     * @Route("/edit{id}", methods="POST", name="auto_admin_rental_station_update",requirements={"id"="\d+"})
     * @Template("AutoAdminBundle:Car:edit.html.twig")
     */
    public function updateAction(Request $req, $id)
    {
        $rentalStation = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalStation')
            ->find($id);
        $form = $this->createForm(new RentalStationType(), $rentalStation, [
            'action' => $this->generateUrl('auto_admin_rental_station_update', ['id' => $rentalStation->getId()]),
            'method' => 'POST'
        ]);

        $form->handleRequest($req);
        if ($form->isValid()) {

            $man = $this->getDoctrine()->getManager();
            $man->persist($rentalStation);
            $man->flush();

            return $this->redirect($this->generateUrl('auto_admin_rental_station_list'));
        }
        return ['form'  => $form->createView()];
    }

    /**
     * @Route("/del/{id}", methods="GET", name="auto_admin_rental_station_delete",requirements={"id"="\d+"})
     */
    public function deleteAction($id)
    {
        $man = $this->getDoctrine()->getManager();
        $station = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalStation')
            ->find($id);
        $man->remove($station);
        $man->flush();

        return $this->redirect($this->generateUrl('auto_admin_rental_station_list'));
    }





    /**
     * @Route("/add/operator/{id}", methods="GET", name="auto_admin_add_station_operator")
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

    /**
     * @Route("/new/operator/{id}", methods="POST", name="auto_admin_create_station_operator")
     * @Template("AutoAdminBundle:Operator:new.html.twig")
     */
    public function createOperatorAction(Request $req,$id)
    {
        $station = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Station')
            ->find($id);

        $form = $this
            ->createFormBuilder()
            ->add('mobile', 'text')
            ->setAction($this->generateUrl('auto_admin_create_station_operator',['id'=>$id]))
            ->setMethod('post')
            ->getForm();

        $form->handleRequest($req);

        if ($form->isValid()) {
            $data = $form->getData();
            $mobile = $data['mobile'];
            $member = $this->getDoctrine()
                ->getRepository('AutoManagerBundle:Member')
                ->findOneBy(['mobile'=>$mobile]);


            if(empty($member)){

                return $this->render(
                    "AutoAdminBundle:Default:message.html.twig",
                    ['message'=>'请先注册用户!']
                );
            }

            if(!in_array(\Auto\Bundle\ManagerBundle\Entity\Member::ROLE_OPERATE,$member->getRoles())){
                return $this->render(
                    "AutoAdminBundle:Default:message.html.twig",
                    ['message'=>'非运营人员!']
                );
            }


            $operator = $this->getDoctrine()
                ->getRepository('AutoManagerBundle:Operator')
                ->findOneBy(['member'=>$member]);

            if(empty($operator)){

                $operator = new \Auto\Bundle\ManagerBundle\Entity\Operator();
                $operator->setMember($member);

            }
            $operator->addStation($station);

            $man = $this->getDoctrine()->getManager();
            $man->persist($operator);
            $man->flush();

            return $this->redirect($this->generateUrl('auto_admin_operator_list'));
        }
        return ['form'  => $form->createView()];
    }







    /**
     * @Route("/rtsl", methods="GET", name="auto_admin_add_station_rtsl")
     * @Template()
     */
    public function rtslAction()
    {
        $cars = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Car')
            ->findAll();
        $qb =  	$this->getDoctrine()
            ->getRepository('AutoManagerBundle:Area')
            ->createQueryBuilder('a');

        $provinceLists =
            $qb
                ->select('a')
                ->where($qb->expr()->isNull('a.parent'))
                ->getQuery()
                ->getResult();
        $provinceids=array();
        foreach($provinceLists as $province){
            $provinceids[]=$province->getId();
        }
        $cities =
            $qb
                ->select('a')
                ->where($qb->expr()->in('a.parent',$provinceids))
                ->andWhere($qb->expr()->like('a.name',':area'))
                ->setParameter('area','%市')
                ->getQuery()
                ->getResult();
        $sqb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:RentalStation')
                ->createQueryBuilder('s')
                ->select('s')
                ->join('s.area','ar')
                ->orderBy('s.createTime', 'DESC');
        $citystations=array();
        /*foreach($cities as $value){
            $cityareas =$this->get('auto_manager.area_helper')->getDistinct($value->getId());
            $cityareas[]=$value->getId();

            $stations = $sqb
                ->where($sqb->expr()->eq('s.online', ':online'))
                ->andWhere($sqb->expr()->in('ar.id', ':area'))
                ->setParameter('online', 1)
                ->setParameter('area',$cityareas)
                ->getQuery()
                ->getResult();

            if(empty($stations)){
                $citystations[$value->getId()]=null;
            }
            $citystations[$value->getId()]=$stations;

        }*/
        return ["cars"=>$cars,"provinceLists"=>$provinceLists,"cities"=>$cities/*,"citystations"=>$citystations*/];
    }

    /**
     * @Route("/reallocation", methods="GET", name="auto_admin_add_station_reallocation")
     * @Template()
     */
    public function reallocationAction()
    {
        return[];
    }

}