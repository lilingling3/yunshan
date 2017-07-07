<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 15/7/22
 * Time: 下午4:53
 */

namespace Auto\Bundle\AdminBundle\Controller;

use Auto\Bundle\ManagerBundle\Form\IllegalRecordType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Auto\Bundle\ManagerBundle\Entity\IllegalRecord;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @Route("/regionIllegalRecord")
 */

class RegionIllegalRecordController extends Controller {

    const PER_PAGE = 20;

    
    /**
     * @Route("/list/{page}", methods="GET", name="auto_admin_region_illegal_record_list",
     * requirements={"page"="\d+"},
     * defaults={"page"=1})
     * @Template()
     */
    public function listAction(Request $req,$page = 1)
    {
        $mobile=$req->query->get('mobile');
        $licensePlace=$req->query->get('licensePlace');
        $rentalCarId=$req->query->get('rentalCarId');

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
                ->getRepository('AutoManagerBundle:IllegalRecord')
                ->createQueryBuilder('l')
                ->select('l')
        ;
        $qb->join('l.rentalCar','r')
            ->join('r.rentalStation','rs')
            ->where($qb->expr()->in('rs.area',$areaIds));

        if($mobile){
            $qb ->join('l.order','o')
                ->join('o.member','m')
                ->andWhere( $qb->expr()->eq('m.mobile',':mobile') )
                ->setParameter('mobile', $mobile);

        }elseif($licensePlace&&$rentalCarId){
            $qb
                ->andWhere( $qb->expr()->eq('r.licensePlate',':licensePlate') )
                ->andWhere( $qb->expr()->eq('r.licensePlace',':licensePlace') )
                ->setParameter('licensePlate', $rentalCarId)
                ->setParameter('licensePlace', $licensePlace);
        }
        $illegals =
            new Paginator(
                $qb

                    ->orderBy('l.id', 'DESC')
                    ->setMaxResults(self::PER_PAGE)
                    ->setFirstResult(self::PER_PAGE * ($page - 1))
            );

        $licensePlaces=$this->getDoctrine()
            ->getRepository('AutoManagerBundle:LicensePlace')
            ->findAll();

        $total = ceil(count($illegals) / self::PER_PAGE);
        return ['illegals'=>$illegals,'licensePlaces'=>$licensePlaces,'page'=>$page,'total'=>$total];
    }

    /**
     * @Route("/new", methods="GET", name="auto_admin_region_illegal_record_new")
     * @Template()
     */
    public function newAction()
    {
        $form = $this->createForm(new IllegalRecordType(), null, [
            'action' => $this->generateUrl('auto_admin_region_illegal_record_create'),
            'method' => 'POST'
        ]);

        return ['form'  => $form->createView()];
    }

    /**
     * @Route("/new", methods="POST", name="auto_admin_region_illegal_record_create")
     * @Template("AutoAdminBundle:IllegalRecord:new.html.twig")
     */
    public function createAction(Request $req)
    {
        $illegal = new \Auto\Bundle\ManagerBundle\Entity\IllegalRecord();

        $form = $this->createForm(new IllegalRecordType(), $illegal, [
            'action' => $this->generateUrl('auto_admin_region_illegal_record_create'),
            'method' => 'POST'
        ]);

        $form->handleRequest($req);

        if ($form->isValid()) {

            $man = $this->getDoctrine()->getManager();
            $man->persist($illegal);
            $man->flush();

            return $this->redirect($this->generateUrl('auto_admin_region_illegal_record_list'));
        }
        return ['form'  => $form->createView()];
    }
    /**
     * @Route("/edit/{id}", methods="GET", name="auto_admin_region_illegal_record_edit",requirements={"id"="\d+"})
     * @Template()
     */
    public function editAction($id)
    {
        $illegal = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:IllegalRecord')
            ->find($id);
        $form = $this->createForm(new IllegalRecordType(), $illegal, [
            'action' => $this->generateUrl('auto_admin_region_illegal_record_update', ['id' => $illegal->getId()]),
            'method' => 'POST'
        ]);

        return [
            'form'  => $form->createView()
        ];
    }


    /**
     * @Route("/edit{id}", methods="POST", name="auto_admin_region_illegal_record_update",requirements={"id"="\d+"})
     * @Template("AutoAdminBundle:Car:edit.html.twig")
     */
    public function updateAction(Request $req, $id)
    {
        $illegal = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:IllegalRecord')
            ->find($id);
        $form = $this->createForm(new IllegalRecordType(), $illegal, [
            'action' => $this->generateUrl('auto_admin_region_illegal_record_update', ['id' => $illegal->getId()]),
            'method' => 'POST'
        ]);

        $form->handleRequest($req);
        if ($form->isValid()) {

            $man = $this->getDoctrine()->getManager();
            $man->persist($illegal);
            $man->flush();

            return $this->redirect($this->generateUrl('auto_admin_region_illegal_record_list'));
        }
        return ['form'  => $form->createView()];
    }


    /**
     * @Route("/del/{id}", methods="GET", name="auto_admin_region_illegal_record_delete",requirements={"id"="\d+"})
     */
    public function deleteAction($id)
    {
        $man = $this->getDoctrine()->getManager();
        $illegal = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:IllegalRecord')
            ->find($id);
        $man->remove($illegal);
        $man->flush();

        return $this->redirect($this->generateUrl('auto_admin_region_illegal_record_list'));
    }

    /**
     * @Route("/add/rentalcar/{id}", methods="GET", name="auto_admin_region_illegal_add_by_rentalcar",
     * requirements={"id"="\d+"})
     * @Template("AutoAdminBundle:IllegalRecord:new.html.twig")
     */
    public function addByRentalCarAction($id)
    {
        $rentalcar = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->find($id);

        $illegal = new \Auto\Bundle\ManagerBundle\Entity\IllegalRecord();

        $illegal->setRentalCar($rentalcar);

        $form = $this->createForm(new IllegalRecordType(), $illegal, [
            'action' => $this->generateUrl('auto_admin_region_illegal_record_create'),
            'method' => 'POST'
        ]);

        return ['form'  => $form->createView(),'rentalcar' => $rentalcar];
    }

    /**
     * @Route("/list/rentalcar/{id}", methods="GET", name="auto_admin_region_illegal_record_list_by_rentalcar",
     * requirements={"id"="\d+"})
     * @Template("AutoAdminBundle:IllegalRecord:renderList.html.twig")
     */
    public function listByRentalCarAction($id)
    {
        $rentalcar = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->find($id);

        $illegals = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:IllegalRecord')
            ->findBy(['rentalCar'=>$rentalcar],['id'=>'desc']);

        return ['illegals'=>$illegals,'rentalcar' => $rentalcar];
    }

    /**
     * @Route("/agentNew{id}", methods="GET", name="auto_admin_region_illegal_agent_new",requirements={"id"="\d+"})
     * @Template()
     */
    public function agentNewAction($id)
    {
        $form = $this
            ->createFormBuilder()
            ->add('Illegal', new IllegalRecordType())
            ->add('mobile', 'text')
            ->setAction($this->generateUrl('auto_admin_region_illegal_agent_create', ['id' => $id]))
            ->setMethod('post')
            ->getForm();

        return ['form'  => $form->createView()];
    }


    /**
     * @Route("/agentNew{id}", methods="POST", name="auto_admin_region_illegal_agent_create",requirements={"id"="\d+"})
     * @Template("AutoAdminBundle:IllegalRecord:agentNew.html.twig")
     */
    public function agentCreateAction(Request $req,$id)
    {

        $Illegal = new \Auto\Bundle\ManagerBundle\Entity\IllegalRecord();
        $form = $this
            ->createFormBuilder()
            ->add('Illegal', new IllegalRecordType(),['data'=>$Illegal])
            ->add('mobile', 'text')
            ->setAction($this->generateUrl('auto_admin_region_illegal_agent_create', ['id' => $id]))
            ->setMethod('post')
            ->getForm();

        $form->handleRequest($req);

        if ($form->isValid()) {
            $data = $form->getData();
            $Illegal = $data['Illegal'];
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
            $operator = $this->getDoctrine()
                ->getRepository('AutoManagerBundle:Operator')
                ->findOneBy(['member'=>$member]);
            if(empty($operator)){

                return $this->render(
                    "AutoAdminBundle:Default:message.html.twig",
                    ['message'=>'请先分配权限!']
                );
            }


            $illegal = $this->getDoctrine()
                ->getRepository('AutoManagerBundle:IllegalRecord')
                ->findOneBy(['id'=>$id]);
            $illegal->setAgent($member);
            $illegal->setIllegalAmount($Illegal->getIllegalAmount());
            $illegal->setRemark($Illegal->getRemark());
            $man = $this->getDoctrine()->getManager();
            $man->persist($illegal);
            $man->flush();


            return $this->redirect($this->generateUrl('auto_admin_region_illegal_record_list'));
        }
       // return ['form'  => $form->createView()];
    }




}