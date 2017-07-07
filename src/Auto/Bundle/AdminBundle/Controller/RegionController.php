<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 15/9/14
 * Time: 下午6:04
 */

namespace Auto\Bundle\AdminBundle\Controller;
use Auto\Bundle\ManagerBundle\Entity\AuthMember;
use Auto\Bundle\ManagerBundle\Form\AuthMemberType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Auto\Bundle\ManagerBundle\Entity\Area;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @Route("/region")
 */
class RegionController extends Controller {

    const PER_PAGE = 20;

    /**
     * @Route("/list/{page}", methods="GET", name="auto_admin_region_list",
     * requirements={"page"="\d+"},
     * defaults={"page"=1})
     * @Template()
     */
    public function listAction(Request $req,$page = 1)
    {
        $mobile = $req->query->get('mobile');
        $name = $req->query->get('name');
        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:Member')
                ->createQueryBuilder('m');
        ;
        if($mobile){
            $qb
                ->andWhere($qb->expr()->eq('m.mobile', ':mobile'))
                ->setParameter('mobile', $mobile);
        }
        if($name){
            $qb
                ->andWhere($qb->expr()->eq('m.name', ':name'))
                ->setParameter('name', $name);
        }


        $members =
            new Paginator(
                $qb
                    ->select('m')
                    ->andWhere($qb->expr()->like('m.roles', ':role'))
                    ->setParameter('role',"%".\Auto\Bundle\ManagerBundle\Entity\Member::ROLE_REGION_MANAGER."%")
                    ->getQuery()
                    ->setMaxResults(self::PER_PAGE)
                    ->setFirstResult(self::PER_PAGE * ($page - 1))
            );
        $total = ceil(count($members) / self::PER_PAGE);
        $tempMember = array();
        foreach ($members as $key => $member) {
            $tempMember[$key]['id'] = $member->getId();
            $tempMember[$key]['name'] = $member->getName();
            $tempMember[$key]['mobile'] = $member->getMobile();
            $roles = $member->getRoles();
            $tempRoleArray = array();
            if(in_array(\Auto\Bundle\ManagerBundle\Entity\Member::ROLE_ADMIN,$roles)){
                $tempRoleArray[] = '管理员';
            }
            if(in_array(\Auto\Bundle\ManagerBundle\Entity\Member::ROLE_COO,$roles)){
                $tempRoleArray[] = '运营总监';
            }
            if(in_array(\Auto\Bundle\ManagerBundle\Entity\Member::ROLE_REGION_MANAGER,$roles)){
                $tempRoleArray[] = '大区经理';
            }
            if(in_array(\Auto\Bundle\ManagerBundle\Entity\Member::ROLE_OPERATE,$roles)){
                $tempRoleArray[] = '运营专员';
            }
            if(in_array(\Auto\Bundle\ManagerBundle\Entity\Member::ROLE_MARKET,$roles)){
                $tempRoleArray[] = '市场主管';
            }
            if(in_array(\Auto\Bundle\ManagerBundle\Entity\Member::ROLE_FINANCE,$roles)){
                $tempRoleArray[] = '财务主管';
            }
            if(in_array(\Auto\Bundle\ManagerBundle\Entity\Member::ROLE_SERVER,$roles)){
                $tempRoleArray[] = '客服主管';
            }
            $tempMember[$key]['roles'] = implode('、',$tempRoleArray);
        }

        return ['members'=>$tempMember,'page'=>$page,'total'=>$total,'mobile'=>$mobile,'name'=>$name];

    }

    /**
     * @Route("/show/{id}", methods="GET", name="auto_admin_region_show")
     * @Template()
     */
    public function showAction($id)
    {
        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->find($id);
        if(empty($member)){
            return $this->render(
                "AutoAdminBundle:Default:message.html.twig",
                ['message'=>'无此用户!']
            );
        }
        $region = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Region')
            ->findOneBy(['member'=>$member]);
        if(empty($region)){
            $region = new \Auto\Bundle\ManagerBundle\Entity\Region();
            $region->setMember($member);
            $man = $this->getDoctrine()->getManager();
            $man->persist($region);
            $man->flush();
        }
        $areaTemp = $region->getAreas();
        $areas = array();
        $areaArr = array();
        $cityArr = array();
        $provinceArr = array();
        foreach ($areaTemp as $key => $area) {
            $areas[$key]['id'] = $area->getId();
            if(empty($area->getParent())){
                $provinceArr[] = $area->getId();
                $oCitys = $area->getChildren()->toArray();
                foreach ($oCitys as $oCity) {
                    $cityArr[] = $oCity->getId();
                    $oAreas = $oCity->getChildren()->toArray();
                    foreach ($oAreas as $oArea) {
                        $areaArr[] = $oArea->getId();
                    }
                }
                $areas[$key]['name'] = $area->getName();
            }else{
                $areaParent1 = $area->getParent();
                if(empty($areaParent1->getParent())){
                    $cityArr[] = $area->getId();
                    $provinceArr[] = $areaParent1->getId();
                    $oAreas = $area->getChildren()->toArray();
                    foreach ($oAreas as $oArea) {
                        $areaArr[] = $oArea->getId();
                    }
                    $areas[$key]['name'] = $areaParent1->getName().' - '.$area->getName();

                }else{
                    $areaParent2 = $areaParent1->getParent();
                    $areaArr[] = $area->getId();
                    $cityArr[] = $areaParent1->getId();
                    $provinceArr[] = $areaParent2->getId();
                    $areas[$key]['name'] = $areaParent2->getName().' - '.$areaParent1->getName().' - '.$area->getName();
                }
            }
        }
        $areacount = array(
                'province'=>count(array_unique($provinceArr)),
                'city'=>count(array_unique($cityArr)),
                'area'=>count(array_unique($areaArr)),
        );


        return ['region'=>$region,'areas'=>$areas,'areacount'=>$areacount];
    }

    /**
     * @Route("/del/{region_id}/{area_id}/", methods="GET", name="auto_admin_region_area_delete",requirements={"id"="\d+"})
     */
    public function deleteAction($region_id,$area_id)
    {
        $region=$this->getDoctrine()
            -> getRepository('AutoManagerBundle:Region')
            ->find($region_id);
        $area = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Area')
            ->find($area_id);
        $region->removeArea($area);
        $man = $this->getDoctrine()->getManager();
        $man->persist($region);
        $man->flush();

        return $this->redirect($this->generateUrl('auto_admin_region_show',["id"=>$region->getMember()->getId()]));
    }

    /**
     * @Route("/set/{id}", methods="GET", name="auto_admin_region_area_set")
     * @Template()
     */
    public function setAction($id)
    {
        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->find($id);
        if(empty($member)){
            return $this->render(
                "AutoAdminBundle:Default:message.html.twig",
                ['message'=>'无此用户!']
            );
        }
        $region = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Region')
            ->findOneBy(['member'=>$member]);
        if(empty($region)){
            $region = new \Auto\Bundle\ManagerBundle\Entity\Region();
            $region->setMember($member);
            $man = $this->getDoctrine()->getManager();
            $man->persist($region);
            $man->flush();
        }

        $provinces = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Area')
            ->findBy(['parent' => null], ['id' => 'ASC']);

        return ['region'=>$region,'provinces'=>$provinces];
    }

    /**
     * @Route("/set/{id}", methods="POST", name="auto_admin_region_area_set_do")
     * @Template()
     */
    public function setDoAction(Request $req,$id)
    {
        $region = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Region')
            ->find($id);
        if(empty($region)){
            return $this->render(
                "AutoAdminBundle:Default:message.html.twig",
                ['message'=>'非法操作!']
            );
        }

        $areaIds = $req->request->get('area');
        $areas = $region->getAreas();
        $areasExist = array();
        foreach ($areas as $area) {
            $areasExist[] = $area->getId();
        }
        if($areaIds && !in_array($areaIds,$areasExist)){
            $areaIds = intval($areaIds);
            $area = $this->getDoctrine()
                ->getRepository('AutoManagerBundle:Area')
                ->find($areaIds);
            if(!empty($area)){
                $region->addArea($area);
                $man = $this->getDoctrine()->getManager();
                $man->persist($region);
                $man->flush();
            }
        }
        return $this->redirect($this->generateUrl('auto_admin_region_show',["id"=>$region->getMember()->getId()]));

    }


}