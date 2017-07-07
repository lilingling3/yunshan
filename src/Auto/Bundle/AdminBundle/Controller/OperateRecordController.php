<?php

namespace Auto\Bundle\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\SecurityContext;
use Auto\Bundle\ManagerBundle\Entity\AuthMember;
use Auto\Bundle\ManagerBundle\Form\AuthMemberType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Auto\Bundle\ManagerBundle\Entity\RentalCar;
use Auto\Bundle\ManagerBundle\Form\RentalCarType;
use Doctrine\ORM\EntityManager;


/**
 * @Route("/operateRecord")
 */
class OperateRecordController extends Controller
{
    const PER_PAGE = 20;

    /**
     * @Route("/list/{page}", methods="GET", name="auto_admin_operateRecord_list",
     * requirements={"page"="\d+"},
     * defaults={"page"=1})
     * @Template()
     */
    public function listAction(Request $req,$page = 1)
    {

        $mobile=$req->query->get('mobile');
        $operate=$req->query->get('operate');
        $starttime= $req->query->get('starttime');
        $endtime= $req->query->get('endtime');
        $object= $req->query->get('object');
        $content= $req->query->get('content');
//        $endtime = date("Y-m-d",strtotime($endtimeOrigin)+86400);
        $objects = array(
            'SMS'=>'短信',
            'AuthMember'=>'用户审核',
            'Car'=>'车型',
            'Station'=>'租赁点',
            'Coupon'=>'优惠券',
            'RentalCar'=>'车辆',
            'Area'=>'地区',
            'Company'=>'车辆归属公司',
            'Color'=>'颜色',
            'RentalPrice'=>'车型定价',
            'CouponActivity'=>'优惠活动',
            'LicensePlace'=>'车牌归属地',
            'BodyType'=>'车身结构',
            'CarLevel'=>'车型级别',
            'CouponKind'=>'优惠券类型',
            'BlackList'=>'黑名单',
            'Appeal'=>'用户申诉',
            'RechargeOrder'=>'用户充值',
            'RefundRecord'=>'退款审核',
            'Member'=>'用户信息',
            "Operator"=>"运营端权限设置"
        );


        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:OperateRecord')
                ->createQueryBuilder('o')
                ->select('o')
                ->join('o.operateMember','om')
        ;

        if($starttime){
            $qb
                ->andWhere($qb->expr()->lte('o.createTime',':endtime'))
                ->andWhere($qb->expr()->gte('o.createTime',':starttime'))
                ->setParameter('endtime', $endtime)
                ->setParameter('starttime', $starttime);
        }
        if($mobile){
            $qb
                -> andWhere($qb->expr()->eq('om.mobile',':id'))
                ->setParameter('id',$mobile);
        }
        if($operate){
            $qb
                -> andWhere($qb->expr()->eq('o.behavior',':behavior'))
                ->setParameter('behavior',$operate);
        }
        if($object){
            $qb
                -> andWhere($qb->expr()->eq('o.objectName',':object'))
                ->setParameter('object',$object);
        }
        if($content){
            $qb
                -> andWhere($qb->expr()->like('o.content',':content'))
                ->setParameter('content',"%" . $content ."%" );
        }


        $operateRecords =
            new Paginator(
                $qb
                    ->orderBy('o.createTime', 'DESC')
                    ->setMaxResults(self::PER_PAGE)
                    ->setFirstResult(self::PER_PAGE * ($page - 1))
            );

        $total = ceil(count($operateRecords) / self::PER_PAGE);
        
        return ['operateRecords'=>$operateRecords,'page'=>$page,'total'=>$total,'objects'=>$objects,
            "operate"=>$operate,'object'=>$object,"mobile"=>$mobile,"starttime"=>$starttime,"endtime"=>$endtime,'content'=>$content];
    }


    /**
     * @Route("/search", methods="POST", name="auto_admin_matter_list_search")
     * @Template()
     */
    public function searchAction(Request $req){

        return $this->redirect(
            $this->generateUrl(
                'auto_admin_operateRecord_list',
                [
                    'operate' => $req->request->get('operate'),
                    'object' => $req->request->get('object'),
                    'mobile' => $req->request->get('mobile'),
                    'starttime' => $req->request->get('starttime'),
                    'endtime' => $req->request->get('endtime'),
                    'content' => $req->request->get('content'),
                ]
            )
        );

    }


}
