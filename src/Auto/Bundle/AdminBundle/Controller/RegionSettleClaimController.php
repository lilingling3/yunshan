<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 15/9/14
 * Time: 下午6:04
 */

namespace Auto\Bundle\AdminBundle\Controller;
use Auto\Bundle\ManagerBundle\Entity\SettleClaim;
use Auto\Bundle\ManagerBundle\Form\SettleClaimType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @Route("/regionSettleClaim")
 */
class RegionSettleClaimController extends Controller {

    const PER_PAGE = 20;

    /**
     * @Route("/list/{page}", methods="GET", name="auto_admin_region_settle_claim_list",
     * requirements={"page"="\d+"},
     * defaults={"page"=1})
     * @Template()
     */
    public function listAction(Request $req,$page = 1)
    {
        $status = $req->query->getInt('status');  // 2审核中 3 审核失败 4 审核成功
        $licensePlace = $req->query->get('licensePlace');
        $plate_number = $req->query->get('rentalCarId');

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


        $licensePlaces=$this
            ->getDoctrine()
            ->getRepository('AutoManagerBundle:LicensePlace')
            ->findAll();
        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:SettleClaim')
                ->createQueryBuilder('m')
                ->select('m')
        ;

        $qb->join('m.insuranceRecord','i')
            ->join('i.rentalCar','r')
            ->join('r.rentalStation','rs')
            ->where($qb->expr()->in('rs.area',$areaIds));
            ;

        if($licensePlace&&$plate_number){
            $qb
                ->andWhere( $qb->expr()->eq('r.licensePlate',':licensePlate') )
                ->andWhere( $qb->expr()->eq('r.licensePlace',':licensePlace') )
                ->setParameter('licensePlate', $plate_number)
                ->setParameter('licensePlace', $licensePlace);
        }
        $claimRecords =
            new Paginator(
                $qb
                    ->orderBy('m.id', 'DESC')
                    ->setMaxResults(self::PER_PAGE)
                    ->setFirstResult(self::PER_PAGE * ($page - 1))
            );

        $total = ceil(count($claimRecords) / self::PER_PAGE);

        return ['claimRecords'=>$claimRecords,'licensePlaces'=>$licensePlaces,'page'=>$page,'total'=>$total,'status'=>$status];
    }

    /**
     * @Route("/new/{id}", methods="GET", name="auto_admin_region_settle_claim_new",requirements={"id"="\d+"})
     * @Template()
     */
    public function newAction($id,Request $req)
    {
        $maintentance=$this
            ->getDoctrine()
            ->getRepository('AutoManagerBundle:MaintenanceRecord')
            ->find($id);
        $form = $this->createForm(new SettleClaimType(), null, [
            'action' => $this->generateUrl('auto_admin_region_settle_claim_create', ['id' =>  $id]),
            'method' => 'POST'
        ]);


        return ['form'  => $form->createView(),'maintentance'=>$maintentance];
    }

    /**
     * @Route("/new/{id}", methods="POST", name="auto_admin_region_settle_claim_create",requirements={"id"="\d+"})
     * @Template("AutoAdminBundle:SettleClaim:new.html.twig")
     */
    public function createAction(Request $req,$id)
    {
        $maintentance=$this
            ->getDoctrine()
            ->getRepository('AutoManagerBundle:MaintenanceRecord')
            ->find($id);
        $settleClaim = new \Auto\Bundle\ManagerBundle\Entity\SettleClaim();

        $form = $this->createForm(new SettleClaimType(), $settleClaim, [
            'action' => $this->generateUrl('auto_admin_region_settle_claim_create', ['id' =>  $id]),
            'method' => 'POST'
        ]);

        $form->handleRequest($req);

        if ($form->isValid()) {

            $settleClaim->setCreateTime(new \DateTime());
            $settleClaim->setClaimTime($maintentance->getDownTime());
            $man = $this->getDoctrine()->getManager();
            $man->persist($settleClaim);
            $man->flush();

            return $this->redirect($this->generateUrl('auto_admin_region_settle_claim_list'));
        }
        return ['form'  => $form->createView()];
    }


    /**
     * @Route("/edit/{id}", methods="GET", name="auto_admin_region_settle_claim_edit",requirements={"id"="\d+"})
     * @Template()
     */
    public function editAction($id)
    {
        $settleClaim = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:SettleClaim')
            ->find($id);

        $form = $this->createForm(new SettleClaimType(), $settleClaim, [
            'action' => $this->generateUrl('auto_admin_region_settle_claim_update', ['id' => $settleClaim->getId()]),
            'method' => 'POST'
        ]);

        return [
            'form'  => $form->createView()
        ];
    }


    /**
     * @Route("/edit{id}", methods="POST", name="auto_admin_region_settle_claim_update",requirements={"id"="\d+"})
     * @Template("AutoAdminBundle:Car:edit.html.twig")
     */
    public function updateAction(Request $req, $id)
    {
        $settleClaim = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:SettleClaim')
            ->find($id);
        $form = $this->createForm(new SettleClaimType(), $settleClaim, [
            'action' => $this->generateUrl('auto_admin_region_settle_claim_update', ['id' => $settleClaim->getId()]),
            'method' => 'POST'
        ]);

        $form->handleRequest($req);
        if ($form->isValid()) {

            $man = $this->getDoctrine()->getManager();
            $man->persist($settleClaim);
            $man->flush();

            return $this->redirect($this->generateUrl('auto_admin_region_settle_claim_list'));
        }
        return ['form'  => $form->createView()];
    }


    /**
     * @Route("/del/{id}", methods="GET", name="auto_admin_region_settle_claim_delete",requirements={"id"="\d+"})
     */
    public function deleteAction($id)
    {
        $man = $this->getDoctrine()->getManager();
        $settleClaim = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:SettleClaim')
            ->find($id);
        $man->remove($settleClaim);
        $man->flush();

        return $this->redirect($this->generateUrl('auto_admin_region_settle_claim_list'));
    }




}