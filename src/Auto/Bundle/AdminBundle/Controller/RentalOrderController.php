<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 15/8/24
 * Time: 上午9:10
 */

namespace Auto\Bundle\AdminBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Auto\Bundle\ManagerBundle\Entity\RentalOrder;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @Route("/rentalOrder")
 */

class RentalOrderController extends Controller {


    const PER_PAGE = 20;

    /**
     * @Route("/list/{page}", methods="get", name="auto_admin_rental_order_list",
     * requirements={"page"="\d+"},
     * defaults={"page"=1})
     * @Template()
     */
    public function listAction(Request $req,$page = 1)
    {
        $status = $req->query->getInt('status');
        $mobile = $req->query->get('mobile');
        $licensePlace = $req->query->get('licensePlace');
        $plateNumber = $req->query->get('plateNumber');

        $idnumber = $req->query->get('idnumber');
        $partnerMember = $req->query->get('partnerMember');
        $province = empty($req->query->get('province')) ? '':$req->query->get('province');
        $city = empty($req->query->getInt('city')) ? '': $req->query->getInt('city');
        $carId = $req->query->get('carId');
        $cars = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Car')
            ->findAll();
        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:RentalOrder')
                ->createQueryBuilder('c')
                ->select('c')
                ->join('c.member','u')
        ;

        if ($partnerMember) {
            $member = $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:Member')
                ->find($partnerMember);
            if ($member) {
                $qb
                    ->andWhere($qb->expr()->eq('c.member', ':member'))
                    ->setParameter('member', $member->getId());
            }
        } elseif ($idnumber) {
            $member = $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:AuthMember')
                ->findOneBy(['IDNumber' => $idnumber]);
            if ($member) {
                $qb
                    ->andWhere($qb->expr()->eq('c.member', ':member'))
                    ->setParameter('member', $member->getMember());
            }
        }

        if($carId){
            $qb->join('c.rentalCar','p')
                ->join('p.car','q')
                ->andWhere( $qb->expr()->eq('q.id',':carId') )
                ->setParameter('carId', $carId);
        }
        if($mobile){
            $qb
                ->andWhere($qb->expr()->eq('u.mobile', ':mobile'))
                ->setParameter('mobile', $mobile);

        }
        if($licensePlace&&$plateNumber){
            $qb->join('c.rentalCar','r')
                ->join('r.licensePlace','l')
                ->andWhere( $qb->expr()->eq('r.licensePlate',':licensePlate') )
                ->andWhere( $qb->expr()->eq('l.id',':licensePlace') )
                ->setParameter('licensePlate', $plateNumber)
                ->setParameter('licensePlace', $licensePlace);
        }

        if($status == \Auto\Bundle\ManagerBundle\Entity\RentalOrder::CANCEL_ORDER){
            $qb ->andWhere($qb->expr()->isNull('c.useTime'))
                ->andWhere($qb->expr()->isNotNull('c.cancelTime'));

        }
        if($status == \Auto\Bundle\ManagerBundle\Entity\RentalOrder::PROCESS_NO_TAKE_ORDER){
            $qb->andWhere($qb->expr()->isNull('c.useTime'))
                ->andWhere($qb->expr()->isNull('c.cancelTime'))
                ->andWhere($qb->expr()->isNull('c.endTime'));
        }
        if($status == \Auto\Bundle\ManagerBundle\Entity\RentalOrder::PROCESS_HAS_TAKE_ORDER){
            $qb->andWhere($qb->expr()->isNotNull('c.useTime'))
                ->andWhere($qb->expr()->isNull('c.endTime'));
        }
        if($status == \Auto\Bundle\ManagerBundle\Entity\RentalOrder::BACK_NO_PAY_ORDER){
            $qb->andWhere($qb->expr()->isNotNull('c.endTime'))
                ->andWhere($qb->expr()->isNull('c.payTime'));
        }

        if($status == \Auto\Bundle\ManagerBundle\Entity\RentalOrder::PAYED_ORDER){
            $qb->andWhere($qb->expr()->isNotNull('c.payTime'));
        }

        if ($province !=='' && $province !=='请选择') {

            $distinctList =  $this->get('auto_manager.area_helper')->getDistinctList($province,$city);

            $qb
                ->join('c.pickUpStation','s')
                ->andWhere($qb->expr()->in('s.area', ':area'))
                ->setParameter('area', $distinctList)
            ;
        }


        $rentalOrders =
            new Paginator(
                $qb
                    ->select('c')
                    ->orderBy('c.id', 'DESC')
                    ->setMaxResults(self::PER_PAGE)
                    ->setFirstResult(self::PER_PAGE * ($page - 1))
            );
        $should_obtain_fee=[];
        $rentalUseTime=[];
        if(!empty($rentalOrders)) {
            foreach ($rentalOrders as $order) {
                if (!isset($should_obtain_fee[$order->getId()])) {
                    $should_obtain_fee[$order->getId()] = 0;
                }

                if (!isset($rentalUseTime[$order->getId()])) {
                    $rentalUseTime[$order->getId()] = 0;
                }

                $should_obtain_fees = $this->get('auto_manager.order_helper')->get_charge_details($order);
                if (!$order->getCancelTime()) {
                    $t=$order->getEndTime()?$order->getEndTime():new \DateTime();
                    $gap=$t->getTimestamp()-$order->getCreateTime()->getTimestamp();

                    $d = floor($gap/3600/24);
                    $h = floor(($gap%(3600*24))/3600);  //%取余
                    $m = floor(($gap%(3600*24))%3600/60);
                    $s = floor(($gap%(3600*24))%60);

                    $rentalUseTime[$order->getId()]="$d 天 $h 小时 $m 分 $s 秒";
                    $should_obtain_fee[$order->getId()] = $should_obtain_fees['cost'];
                } else {
                    $rentalUseTime[$order->getId()]=0;
                    $should_obtain_fee[$order->getId()] = 0;
                }

            }
        }

        $licensePlaces=$this->getDoctrine()
            ->getRepository('AutoManagerBundle:LicensePlace')
            ->findAll();

        $total = ceil(count($rentalOrders) / self::PER_PAGE);
        return [
            'rentalOrders'=>$rentalOrders,'should_obtain_fee'=>$should_obtain_fee,'rentalUseTime'=>$rentalUseTime,'cars'=>$cars,
            'licensePlaces'=>$licensePlaces,'page'=>$page,'total'=>$total,'carstatus'=>$status,"mobile"=>$mobile,'carId'=>$carId,
            "licensePlace"=>$licensePlace,"plateNumber"=>$plateNumber,"idnumber"=>$idnumber,'province'=>$province,'city'=>$city,
            'partnerMember'=>$partnerMember
        ];

    }

    /**
     * @Route("/rentalOrder/search", methods="POST", name="auto_admin_rental_order_search")
     * @Template()
     */
    public function rentalOrderSearchAction(Request $req){
        return $this->redirect(
            $this->generateUrl(
                'auto_admin_rental_order_list',
                [
                    'status' => trim($req->request->getInt('status')),
                    'mobile' => trim($req->request->get('mobile')),
                    'name' => trim($req->request->get('name')),
                    'licensePlace'=> trim($req->request->get('licensePlace')),
                     'plateNumber' => trim($req->request->get('plateNumber')),
                    'idnumber' => trim($req->request->get('idnumber')),
                ]
            )
        );

    }

    /**
     * @Route("/show/{id}", methods="GET", name="auto_admin_rental_order_show",
     * requirements={"id"="\d+"})
     * @Template()
     */
    public function showAction($id,$page=1)
    {
        $order = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalOrder')
            ->find($id);
        $useTime=0;$day_time=0;$night_time=0;$order_cost=[];
        if(!empty($order)) {
                if (!$order->getCancelTime()) {
                    $t=$order->getEndTime()?$order->getEndTime():new \DateTime();
                    $gap=$t->getTimestamp()-$order->getCreateTime()->getTimestamp();

                    $d = floor($gap/3600/24);
                    $h = floor(($gap%(3600*24))/3600);  //%取余
                    $m = floor(($gap%(3600*24))%3600/60);
                    $s = floor(($gap%(3600*24))%60);

                    $useTime="$d 天 $h 小时 $m 分 $s 秒";

                } else {
                    $useTime=0;
                }

            $order_cost_one = $this->get('auto_manager.order_helper')->get_rental_order_cost($order);
           // var_dump($order_cost);
            $order_cost=[];
            if($order_cost_one){
            foreach($order_cost_one as $cost){
                if(is_array($cost)){
                    foreach($cost as $c){
                        $order_cost[]=$c ;
                    }
                }
            }
            }
        }

        $rentalTime=[];
        if($order_cost){
            foreach($order_cost as $cost){
                if(!isset($rentalTime[$cost['rentalPriceID']])){
                    $rentalTime[$cost['rentalPriceID']]=0;
                }
                $gap=$cost['time'];

                $d = floor($gap/3600/24);
                $h = floor(($gap%(3600*24))/3600);  //%取余
                $m = floor(($gap%(3600*24))%3600/60);
                $s = floor(($gap%(3600*24))%60);

                $rentalTime[$cost['rentalPriceID']]="$d 天 $h 小时 $m 分 $s 秒";
            }
        }


        $cost_details = $this->get('auto_manager.order_helper')->get_rental_order_cost($order);

        $auth = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:AuthMember')
            ->findOneBy(["member"=>$order->getMember()]);
        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:BlackList')
                ->createQueryBuilder('a')
                ->select('a')
                ->join('a.authMember','b')
        ;
        if($auth)
        {
            $blacked=$qb
                ->andWhere($qb->expr()->eq('b.IDNumber', ':IDNumber'))
                ->andWhere($qb->expr()->orX(
                    $qb->expr()->gt('a.endTime', ':today'),
                    $qb->expr()->isNull('a.endTime')
                ))
                ->setParameter('today', (new \DateTime()))
                ->setParameter('IDNumber', $auth->getIDNumber())
                ->getQuery()
                ->getResult()
            ;
        }else{
            $blacked = null;
        }

        $price=$this
            ->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalPrice')
            ->findBy(["car"=>$order->getRentalCar()->getCar()]);

        $illegal=$this
            ->getDoctrine()
            ->getRepository('AutoManagerBundle:IllegalRecord')
            ->findBy(["order"=>$order]);

        $rental_order = call_user_func($this->get('auto_manager.order_helper')->get_rental_order_normalizer(),
            $order);

        $stop_car_order_data= $this->get('auto_manager.easy_stop_helper')->stopCarOrderHistory($rental_order['rentalCar']['license'],$rental_order['useTime'],$rental_order['endTime']);

        $password = '';
        if($rental_order['status'] == \Auto\Bundle\ManagerBundle\Entity\RentalOrder::PROCESS_HAS_TAKE_ORDER){
            //租赁中的订单获取开车密码
            $key = $order->getRentalCar()->getDeviceCompany()->getEnglishName().'-password-'.$order->getRentalCar()->getBoxId();
            $redis = $this->container->get('snc_redis.default');
            $redis_cmd= $redis->createCommand('GET',array($key));
            $password = $redis->executeCommand($redis_cmd);
        }

        return ['order'=>$order,'cost_details'=>$cost_details,'auth'=>$auth,'illegals' => $illegal ,'page'=>$page,
            'rentalOrder' => $rental_order,'rentalTime'=>$rentalTime,'useTime'=>$useTime,'blacked'=>$blacked,
            'order_cost'=>$order_cost,'day_time'=>$day_time,'night_time'=>$night_time,'park_order_data'=> $stop_car_order_data,
            'password'=>$password
        ];

    }

}