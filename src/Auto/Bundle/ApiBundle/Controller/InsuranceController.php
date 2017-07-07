<?php
/**
 * Created by PhpStorm.
 * User: luyao
 * Date: 16/12/17
 * Time: 下午1:18
 */

namespace Auto\Bundle\ApiBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\HttpFoundation\Request;
use Auto\Bundle\ManagerBundle\Entity\Message;


/**
 * @Route("/insurance")
 */
class InsuranceController extends BaseController {

    const PER_PAGE = 5;

    const CERTITYPE_ID_CARD  = 1; // 身份证
    const CERTITYPE_PASSPORT = 2; // 护照
    const CERTITYPE_OFFICER  = 3; // 军官证
    const CERTITYPE_HK_TW    = 4; // 港台同胞证


    const CERTI_SYS_ERR             = 1001;   // 系统异常
    const CERTI_REQ_TIMEOUT         = 1002;   // 请求超时
    const CERTI_APPKEY_NULL         = 2003;   // appkey不能为空
    const CERTI_CANT_GET_ID         = 3001;   // 无法通过商户编号获取商户
    const CERTI_CANT_GET_KEY        = 3002;   // 无法通过商户编号获取商户私钥
    const CERTI_INSURANCE_REPEAT    = 3003; // 用户重复投保
    const CERTI_PARM_TIME_NULL      = 4001; // timestamp不能为空
    const CERTI_REQ_TIMEOUT_5S      = 4002; // timestamp与当前时间时间差大于5秒,请求超时
    const CERTI_PARM_SIGN_NULL      = 4003; // sign不能为空
    const CERTI_PARM_BIZ_NULL       = 4004; // bizcontent业务数据不能为空
    const CERTI_PARM_BIZ_DEENTRYPT_FALSE = 4005;  //  bizcontent解密失败
    const CERTI_PARM_PROD_TYPE_NULL = 4006; // 产品类型不能为空
    const CERTI_PARM_ORDER_NULL     = 4007; // 渠道订单编号不能为空
    const CERTI_PARM_NAME_NULL      = 4012; // 租赁人姓名不能为空
    const CERTI_PARM_ID_TYOE_NULL   = 4013; // 租赁人证件类型不能为空
    const CERTI_PARM_MOBILE_NULL    = 4014; // 租赁人电话号码不能为空
    const CERTI_PARM_ID_NULL        = 4015;     // 租赁人证件号码不能为空
    const CERTI_PARM_LICENSE_PLATE_NULL = 4018; // 租赁车辆车牌号不能为空
    const CERTI_PARM_LON_NULL       = 4019; // 经度不能为空
    const CERTI_PARM_LAT_NULL       = 4020; // 纬度不能为空
    const CERTI_PARM_GPS_NULL       = 4021; // 坐标系取值不能为空

    /**
     * @Route("/add", methods="POST",name="auto_api_insurance_add")
     */
    public function addAction(Request $req)
    {

        $uid  = $req->request->get('OrderID');

        // 根据单号找到人
        $order = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalOrder')
            ->findOneBy(['id'=>$uid]);

        if(empty($order)){

            return new JsonResponse([
                'errorCode'    =>  self::E_NO_ORDER,
                'errorMessage' =>  self::M_NO_ORDER
            ]);
        }

        $member = $order->getMember();

        if (empty($member)) {

            return new JsonResponse([
                'errorCode'    =>  self::E_NO_ORDER,
                'errorMessage' =>  self::M_NO_ORDER
            ]);
        }

        $auth_member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:AuthMember')
            ->findOneBy(['member'=>$member]);

        if (empty($auth_member)) {
            
            return new JsonResponse([
                'errorCode'    =>  self::E_MEMBER_NO_AUTH,
                'errorMessage' =>  self::M_MEMBER_NO_AUTH
            ]);
        }
        
        $rentalCar = $order->getRentalCar();

        if (empty($rentalCar)) {

            return new JsonResponse([
                'errorCode'    =>  self::E_NO_RENTAL_CAR,
                'errorMessage' =>  self::M_NO_RENTAL_CAR
            ]);       
        }

        // 获取当前车的位置
        $carLocation = $this->get('auto_manager.fee_zu_helper')->findCarLocation($order->getRentalCar()->getBoxid());

        if (empty($carLocation) || $carLocation['longitude'] <0 || $carLocation['latitude'] < 0) {

            $redis = $this->container->get('snc_redis.default');
        	$redis_cmd_gps= $redis->createCommand('hget',array("feeZu-car-curlocation",$order->getRentalCar()->getBoxid()));
            $historyData = $redis->executeCommand($redis_cmd_gps);

            if ($historyData) {

                $history = json_decode($historyData);
                
                $carLocation['longitude'] = $history[0];
                $carLocation['latitude']  = $history[1];

            } else {
                $carLocation['longitude'] = 0;
                $carLocation['latitude'] = 0;
            }
        }

        $bizcontent = 
            [
                'productNo'         => 'TCL(ws)',
                'channelOrderNo'    => (string)$order->getId(),
                'rentalPersonName'  => (string)$member->getName(),
                'rentalPersonPhone' => (string)$member->getMobile(),
                'rentalPersonCertiType' => (string)self::CERTITYPE_ID_CARD,
                'carModel'          => (string)$rentalCar->getCar()->getName(),
                'rentalPersonCertiNo'   => (string)$auth_member->getIdNumber(),
                'rentalCarLicPlate'     => (string)($rentalCar->getLicensePlace()->getName().$rentalCar->getLicensePlate()),
                'lon'               => (string)(!empty($carLocation['longitude']) ? $carLocation['longitude']:0) ,
                'lat'               => (string)(!empty($carLocation['latitude']) ? $carLocation['latitude'] :0),
                'coordstype'        => 'gps'
            ];

        $result = $this->get('auto_manager.insurance_helper')->add($bizcontent);


        // 保存数据往来记录
        $record = new \Auto\Bundle\ManagerBundle\Entity\RentalCarInsuranceRecord();
        $record->setRequestLog(json_encode($bizcontent));
        $record->setResponseLog(json_encode($result['response']));
        
        $man = $this->getDoctrine()->getManager();
        $man->persist($record);
        $man->flush();


        if ($result) {
            
            // 签名是否通过
            if ($result['sign'] == true) {

                $content = json_decode($result['response']->bizcontent);

                // 如果存在错误
                if ($result['response']->errorcode !== 0) {

                    return new JsonResponse([
                        'errorCode'    => $result['response']->errorcode,
                        'errorMessage' => $content->msg
                    ]);
                }

                $company = $this->getDoctrine()
                                ->getRepository('AutoManagerBundle:Company')
                                ->findOneBy(['id'=>7]);
                // 保存下单记录
                $InsuranceRecord = new \Auto\Bundle\ManagerBundle\Entity\InsuranceRecord();
                $InsuranceRecord->setRentalCar($rentalCar);
                $InsuranceRecord->setCompany($company);
                $InsuranceRecord->setInsurance(3);
                $InsuranceRecord->setInsuranceAmount(0);
                $InsuranceRecord->setInsuranceTime(new \DateTime());
                $InsuranceRecord->setStartTime(new \DateTime());
                $InsuranceRecord->setInsuranceNumber($content->policyNo);
                
                $man = $this->getDoctrine()->getManager();
                $man->persist($InsuranceRecord);
                $man->flush();

            } else {

                return new JsonResponse([
                    'errorCode'    => -1,
                    'errorMessage' => '验签没有通过',
                    '1' => $result['response']->bizcontent,
                ]);
            }
        }

        return new JsonResponse([
            'errorCode'  => self::E_OK,
            'l' => $result
        ]);

    }


    /**
     * @Route("/underwrite", methods="POST",name="auto_api_under_write")
     */
    public function underWriteAction(Request $req)
    {

        $uid  = $req->request->get('OrderID');

        // 根据单号找到人
        $order = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalOrder')
            ->findOneBy(['id'=>$uid]);

        if(empty($order)){

            return new JsonResponse([
                'errorCode'    =>  self::E_NO_ORDER,
                'errorMessage' =>  self::M_NO_ORDER
            ]);
        }

        $member = $order->getMember();

        if (empty($member)) {
            
            return new JsonResponse([
                'errorCode'    =>  self::E_NO_ORDER,
                'errorMessage' =>  self::M_NO_ORDER
            ]);
        }

        $auth_member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:AuthMember')
            ->findOneBy(['member'=>$member]);

        if (empty($auth_member)) {
            
            return new JsonResponse([
                'errorCode'    =>  self::E_MEMBER_NO_AUTH,
                'errorMessage' =>  self::M_MEMBER_NO_AUTH
            ]);
        }

        
        $rentalCar = $order->getRentalCar();

        if (empty($rentalCar)) {

            return new JsonResponse([
                'errorCode'    =>  self::E_NO_RENTAL_CAR,
                'errorMessage' =>  self::M_NO_RENTAL_CAR
            ]);       
        }

        // 获取当前车的位置
        $redis = $this->container->get('snc_redis.default');
        $redis_cmd_gps = $redis->createCommand('hget',array("feeZu-car-curlocation", $rentalCar->getBoxid()));
        $gps_data = json_decode($redis->executeCommand($redis_cmd_gps));


        $qb = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:InsuranceRecord')
            ->createQueryBuilder('i')
        ;

        // 取得保险单号
        $insurance = $qb
            ->select('i')
            ->where($qb->expr()->eq('i.rentalCar', ":carId"))
            ->andWhere($qb->expr()->isNull('i.endTime'))
            ->setParameter('carId', $rentalCar)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneorNullResult();


        if (empty($insurance)) {

            // 没有找到保险单号
            return new JsonResponse([
                'errorCode'    =>  self::E_NO_INSURANCE_EXIST,
                'errorMessage' =>  self::M_NO_INSURANCE_EXIST
            ]);    

        }

        $bizcontent = 
            [
                'policyNo'  => (string)$insurance->getInsuranceNumber(),
                'productNo' => 'TCL(ws)',
                'lon'       => (string)$gps_data[0],
                'lat'       => (string)$gps_data[1],
                'coordstype'=> 'gps'

            ];

        $result = $this->get('auto_manager.insurance_helper')->underwrite($bizcontent);

        // 保存数据往来记录
        $record = new \Auto\Bundle\ManagerBundle\Entity\RentalCarInsuranceRecord();
        $record->setRequestLog(json_encode($bizcontent));
        $record->setResponseLog(json_encode($result['response']));
        
        $man = $this->getDoctrine()->getManager();
        $man->persist($record);
        $man->flush();


        if ($result) {
            
            $insurance->setEndTime(new \DateTime());

            $man = $this->getDoctrine()->getManager();
            $man->persist($insurance);
            $man->flush();
        }

        return new JsonResponse([
            'errorCode'  => self::E_OK,
            'bizcontent' => $bizcontent,
            'result'     => $result 
            
        ]);
    }






}