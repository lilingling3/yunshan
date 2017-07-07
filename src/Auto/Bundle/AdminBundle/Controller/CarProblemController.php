<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 15/7/20
 * Time: 下午2:54
 */

namespace Auto\Bundle\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Auto\Bundle\ManagerBundle\Entity\RentalOrder;
use Doctrine\ORM\Tools\Pagination\Paginator;


/**
 * @Route("/carproblem")
 */
class CarProblemController extends Controller {
    const PER_PAGE = 20;

    /**
     * @Route("/add/{image_label}", methods="GET")
     * @Template()
     */
    public function addAction($image_label)
    {
        $order_id = $image_label;
        $rentalOrder = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalOrder')
            ->find($order_id);
        $mobile = $rentalOrder->getMobile();
        $name = $rentalOrder->getMember()->getName();
        $carType =$rentalOrder->getRentalCar()->getCar()->getName();
        $licensePlace = $rentalOrder->getRentalCar()->getLicensePlace()->getName();;
        $plateNumber = $rentalOrder->getRentalCar()->getLicensePlate();

        $problem = '7';
        $coupon = 'null';
        $state = 102;
        $CarProblem = new \Auto\Bundle\ManagerBundle\Entity\CarProblem();

        $CarProblem->setMobile("$mobile");
        $CarProblem->setName("$name");
        $CarProblem->setCarType("$carType");
        $CarProblem->setLicensePlace("$licensePlace");
        $CarProblem->setPlateNumber("$plateNumber");
        $CarProblem->setCreateTime(new \DateTime());
        $CarProblem->setProblem("$problem");
        $CarProblem->setCoupon("$coupon");
        $CarProblem->setState("$state");

        $man = $this->getDoctrine()->getManager();
        $man->persist($CarProblem);
        $man->flush();
    }


    /**
     * @Route("/list/{page}", methods="get", name="auto_admin_carproblem_list",
     * requirements={"page"="\d+"},
     * defaults={"page"=1})
     * @Template()
     */
    public function listAction(Request $req,$page = 1)
    {
        $state = $req->query->getInt('state');
        $mobile = $req->query->get('mobile');
        $licensePlace = $req->query->get('licensePlace');
        $plateNumber = $req->query->get('plateNumber');

        $problem = empty($req->query->getInt('problem')) ? '': $req->query->getInt('problem');
        $carType = $req->query->get('carId');
        $cars = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Car')
            ->findAll();

        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:CarProblem')
                ->createQueryBuilder('c')
                ->select('c')
        ;
        //$mobile = 15210431987;
        if($mobile){
            $qb
                ->andWhere($qb->expr()->eq('c.mobile', ':mobile'))
                ->setParameter('mobile', $mobile);
        }
        //$licensePlace = '京';
        //$plateNumber = 'M00153';
        if($licensePlace&&$plateNumber){
            $qb
                ->andWhere( $qb->expr()->eq('c.licensePlace',':licensePlace') )
                ->andWhere( $qb->expr()->eq('c.plateNumber',':plateNumber') )
                ->setParameter('plateNumber', $plateNumber)
                ->setParameter('licensePlace', $licensePlace);
        }
        //$carType = '奇瑞EQ';
        if($carType){
            switch($carType){
                case 1:
                    $carTypeFind = '奇瑞EQ';
                    break;
                case 2:
                    $carTypeFind = '帝豪EV';
                    break;
                default:
                    $carTypeFind = '奇瑞EQ';
            }
            $qb
                ->andWhere($qb->expr()->eq('c.carType', ':carTypeFind'))
                ->setParameter('carTypeFind', $carTypeFind);
        }
        //$state = '未处理';
        if($state){
            switch($state){
                case \Auto\Bundle\ManagerBundle\Entity\CarProblem::ALREADY:
                    $stateFind = '已处理';
                    break;
                case \Auto\Bundle\ManagerBundle\Entity\CarProblem::UNTREATED:
                    $stateFind = '未处理';
                    break;
                case \Auto\Bundle\ManagerBundle\Entity\CarProblem::PENDING:
                    $stateFind = '待处理';
                    break;
                case \Auto\Bundle\ManagerBundle\Entity\CarProblem::REPEAT:
                    $stateFind = '重复';
                    break;
                case \Auto\Bundle\ManagerBundle\Entity\CarProblem::FALSE:
                    $stateFind = '不实';
                    break;
                default:
                    $stateFind = '未处理';
            }
            $qb
                ->andWhere($qb->expr()->eq('c.state', ':stateFind'))
                ->setParameter('stateFind', $stateFind);
        }
        //$problem = '保险杠破损';
        if($problem){
            switch($problem){
                case 201;
                    $problemFind = '车辆异常';
                    break;
                case 202;
                    $problemFind = '车身剐蹭';
                    break;
                case 203;
                    $problemFind = '前后挡风破损';
                    break;
                case 204;
                    $problemFind = '反光镜破损';
                    break;
                case 205;
                    $problemFind = '门窗破损';
                    break;
                case 206;
                    $problemFind = '门窗未关';
                    break;
                case 207;
                    $problemFind = '雨刷器破损';
                    break;
                case 208;
                    $problemFind = '保险杠破损';
                    break;
            }
            $qb
                ->andWhere('c.problem LIKE :problemFind')
                ->setParameter('problemFind', "%$problemFind%");
        }
        $carProblem =
            new Paginator(
                $qb
                    ->orderBy('c.imageLabel', 'DESC')
                    ->setMaxResults(self::PER_PAGE)
                    ->setFirstResult(self::PER_PAGE * ($page - 1))
            );
        $licensePlaces=$this->getDoctrine()
            ->getRepository('AutoManagerBundle:LicensePlace')
            ->findAll();

        $total = ceil(count($carProblem) / self::PER_PAGE);
        //var_dump($total);die;

        return [
            'cars'=>$cars,'carProblem'=>$carProblem,'licensePlaces'=>$licensePlaces,"licensePlace"=>$licensePlace,"plateNumber"=>$plateNumber
            ,'total'=>$total,'state'=>$state,'page'=>$page,'mobile'=>$mobile,'state'=>$state,'problem'=>$problem,'carId'=>$carType
        ];

    }

    /**
     * @Route("/show/{imageLabel}", methods="GET", name="auto_admin_carproblem_show",
     * requirements={"imageLabel"="\d+"})
     * @Template()
     */
    public function showAction($imageLabel,$bucket='carproblem')
    {
        //获取指定订单号的详细数据
        $carProblemResult =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:CarProblem')
                ->findOneBy(["imageLabel"=>$imageLabel]);
        ;
        //var_dump($carProblemResult);die;
        //$state = $carProblemResult->getState();
        $coupon = $carProblemResult->getCoupon();
        //var_dump($coupon);die;
        //通过curlhelper获取指定订单号的图片数据
        $baseUrl = $this->get('auto_manager.curl_helper')->base_url();
        $url = $baseUrl."/api/qiniu/downloadqiniu/$bucket/$imageLabel";
        // var_dump($url,$baseUrl,$this->generateUrl);die;
        $qiniuImage = $this->get('auto_manager.curl_helper')->get_url_contents($url);
        //var_dump($qiniuImage);die;
        //获取优惠券列表
        $couponKind =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:CouponKind')
                ->findAll();
        ;
        //var_dump($couponKind);die;
        return[
            'carProblemResult'=>$carProblemResult,'qiniuImage'=>$qiniuImage,'coupon'=>$coupon,'couponKind'=>$couponKind
        ];

    }
    /**
     * @Route("/coupon", methods="GET", name="auto_admin_carproblem_coupon",
     * requirements={"imageLabel"="\d+"})
     * @Template()
     */
    public function couponAction()
    {
        $imageLabel = isset($_GET['imageLabel'])?$_GET['imageLabel']:null;
        $couponKind = isset($_GET['couponKind'])?$_GET['couponKind']:null;
        //判断传入参数是否为空
        if(empty($imageLabel) || empty($couponKind)){
            echo "未传入订单号和优惠券信息";exit;
        }
        $RentalOrder = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalOrder')
            ->find($imageLabel)
        ;
        if(!$RentalOrder){
            echo'无此订单信息';
            sleep(3);
            return $this->redirect($this->generateUrl('auto_admin_carproblem_show',["imageLabel"=>$imageLabel]));
        }
        //要发放优惠券的用户
        $member = $RentalOrder->getMember();
        //优惠券类型
        $CouponKind = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:CouponKind')
            ->find($couponKind)
        ;
        //添加的优惠券活动，统一为：车辆问题上报优惠券
        $CouponActivity = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:CouponActivity')
            ->find(12)
        ;
        //发放优惠券：coupon
        $this->get("auto_manager.coupon_helper")->send_coupon($member,$CouponKind,$CouponActivity);
        //更新car_problem数据，添加优惠券名称
        $CarProblem = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:CarProblem')
            ->findOneBy(['imageLabel'=>$imageLabel])
        ;
        //优惠券名称
        $coupon = $CouponKind->getName();

        $CarProblem->setCoupon($coupon);
        $man = $this->getDoctrine()->getManager();
        $man->persist($CarProblem);
        $man->flush();

        return $this->redirect($this->generateUrl('auto_admin_carproblem_show',["imageLabel"=>$imageLabel]));
    }
}