<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 15/8/13
 * Time: 上午11:32
 */

namespace Auto\Bundle\ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Exception\RuntimeException;

/**
 * @Route("/rentalCar")
 */
class RentalCarController extends BaseController
{
    const PER_PAGE = 20;
    const UNSELECTED = 0;


    /**
     * @Route("/yunshanCallback", methods="POST")
     */

    public function yunshanCallbackAction(Request $req){

        $a = $req->request->getIterator();

        $array = null;
        if(is_object($a)){
            foreach($a as $k=>$v){
                $array[$k] = $v;
            }
        }
        $json = json_encode($array);

        $redis = $this->container->get('snc_redis.default');
        $redis_cmd = $redis->createCommand('set',array('yunshan_test',$json));
        $redis->executeCommand($redis_cmd);
        $redis_cmd= $redis->createCommand('EXPIRE',array('yunshan_test',3600));
        $redis->executeCommand($redis_cmd);

        $result = json_decode($json,true);

        $execute = json_decode($result['r_content'],true);

        //记录操作log
        $carPlate = $execute['carPlate'];
        $orderId = $execute['order_id'];
        //截取memberId和操作详情
        $memberId  = substr($orderId,strripos($orderId,'_')+1);
        $operateId = substr($orderId,strripos($orderId,'-')+1,1);

        $action = '';
        switch($operateId){
            case 1:
                $action = 'Lock';
                break;
            case 2:
                $action = 'UnLock';
                break;
            case 3:
                $action = 'DisconnectingCircuit';
                break;
            case 4:
                $action = 'ConnectionCircuit';
                break;
            case 5:
                $action = 'Find';
        }

        $sendStt = $execute['send_stt'];
        //发送状态
        $sendState = '';
        switch($sendStt){
            case 1:
                $sendState = '发送失败';
                break;
            case 2:
                $sendState = '发送成功';
                break;
            default:
                $sendState = '异常';
        }

        $executeStt = $execute['execute_stt'];
        //执行状态
        $executeState = '';
        switch($executeStt){
            case 1:
                $executeState = '执行失败';
                break;
            case 2:
                $executeState = '执行成功';
                break;
            case 3:
                $executeState = '非法订单';
                break;
            case 4:
                $executeState = '设备未回应';
                break;
            default:
                $executeState = '异常';
        }

        $time = date('Y-m-d H:i:s');

        $txt = "[$time] operate.INFO: $memberId $action $carPlate $sendState $executeState [] [] yunshanCallback_test \n";
        file_put_contents('/data/logs/tbox_operate.log', "$txt", FILE_APPEND);

        return new JsonResponse([
            'errorCode' => self::E_OK,
            'code' => ''
        ]);
    }


    /**
     * @Route("/getCarsByStationtest", methods="POST",name="auto_api_get_cars_by_station_test")
     */

    public function stationRentalCarsTestAction(Request $req)
    {
        $sid = $req->request->get('stationID');
        return new JsonResponse([
            'errorCode' => self::E_NO_STATION,
            'errorMessage' => self::M_NO_STATION,
            'sid' => $sid
        ]);
    }

    /**
     * @Route("/getCarsByStation", methods="POST",name="auto_api_get_cars_by_station")
     */

    public function stationRentalCarsAction(Request $req)
    {

        $sid = $req->request->getInt('stationID');
        $page = $req->request->getInt('page') ? $req->request->getInt('page') : 1;

        /*
         * var $rentalStation = \Auto\Bundle\ManagerBundle\Entity\RentalStation
         */
        $rentalStation = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalStation')
            ->findOneBy(['id' => $sid, 'online' => 1]);

        if (empty($rentalStation)) {
            return new JsonResponse([
                'errorCode' => self::E_NO_STATION,
                'errorMessage' => self::M_NO_STATION
            ]);
        }

        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:RentalCar')
                ->createQueryBuilder('c');

        $rentalCars =

            $qb
                ->select('c')
                ->join('c.online', 'o')
                ->where($qb->expr()->eq('c.rentalStation', ':station'))
                ->andWhere($qb->expr()->eq('o.status', ':status'))
                ->orderBy('c.id', 'DESC')
                ->setParameter('station', $rentalStation)
                ->setParameter('status', 1)
                ->getQuery()
                ->getResult();


        $rental_cars = array_map($this->get('auto_manager.rental_car_helper')->get_rental_car_normalizer(),
            $rentalCars);

        $rental_able_cars = [];


        foreach ($rental_cars as $car) {

            if ($car['status'] == \Auto\Bundle\ManagerBundle\Entity\RentalCar::RENTAL_ABLE) {

                $rental_able_cars[] = $car;

            }

        }

        uasort($rental_able_cars, function ($a, $b) {
            if ($a['mileage'] == $b['mileage']) return 0;
            return ($a['mileage'] < $b['mileage']) ? 1 : -1;
        });

        return new JsonResponse([
            'errorCode' => self::E_OK,
            'rentalCars' => array_values($rental_able_cars),
            'page' => $page,
            'rentalStation' => call_user_func($this->get('auto_manager.station_helper')->get_station_normalizer()
                , $rentalStation),
            'pageCount' => 1
        ]);

    }


    /**
     * @Route("/broken/feeZu", methods="post", name="auto_admin_broken_fee_zu_rentalcar")
     * @Template()
     */
    public function brokenFeeZuAction()
    {
        file_put_contents('/data/logs/broken_feeZu_notice.log', $_POST);

        echo "OK";
        exit;
    }


    /**
     * @Route("/choose", methods="POST" ,name="auto_api_rentalCar_choose")
     */
    public function chooseAction(Request $req)
    {

        $id = $req->request->getInt('rentalCarID');
        $rental_car = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->find($id);

        if (empty($rental_car)) {
            return new JsonResponse([
                'errorCode' => self::E_NO_RENTAL_CAR,
                'errorMessage' => "该车不存在",
            ]);
        }

        return new JsonResponse(
            [
                'errorCode' => self::E_OK,
                'rentalCar' => call_user_func($this->get('auto_manager.rental_car_helper')->get_rental_car_normalizer(), $rental_car),
            ]
        );

    }

    function get_random_integer($length)
    {
        $key = '';
        for ($i = 0; $i < $length; $i++) {
            $key .= rand(1, 5);    //生成php随机数
        }
        return $key;
    }


    /**
     * @Route("/cloud/box/operate", methods="POST")
     */
    public function cloudBoxAction(Request $req)
    {

        $id = $req->request->get('boxID');
        $operate = $req->request->get('operate');
        $redis = $this->container->get('snc_redis.default');

        $redis_cmd = $redis->createCommand('lpush', array('cloudBox-operate-' . $id, $operate));
        $redis->executeCommand($redis_cmd);

        if ($operate == 'startShare') {

            $password = $this->get_random_integer(6);

            $result = $this->get('auto_manager.cloud_box_helper')->operate($id, 'encode', $password);

            $redis_cmd = $redis->createCommand('set', array('cloudBox-password-' . $id, $password));
            $redis->executeCommand($redis_cmd);


        } else {
            $result = $this->get('auto_manager.cloud_box_helper')->operate($id, $operate);

        }


        if ($result) {

            return new JsonResponse(
                [
                    'errorCode' => self::E_OK,
                    "result" => $result
                ]
            );

        } else {

            return new JsonResponse(
                [
                    'errorCode' => self::E_OPERATE_FAIL,
                    "result" => $result
                ]
            );
        }

    }

    /**
     * @Route("/cloud/box/password", methods="POST")
     */
    public function getPasswordAction(Request $req)
    {

        $id = $req->request->get('boxID');

        $redis = $this->container->get('snc_redis.default');
        $redis_cmd = $redis->createCommand('get', array('cloudBox-password-' . $id));
        $password = $redis->executeCommand($redis_cmd);

        return new JsonResponse(
            [
                'errorCode' => self::E_OK,
                'password' => $password
            ]
        );


    }

    /**
     * @Route("/cloud/box/delete", methods="POST")
     */
    public function deleteBoxAction(Request $req)
    {

        $id = $req->request->get('boxID');

        $redis = $this->container->get('snc_redis.default');

        $redis_cmd = $redis->createCommand('lrem', array('cloud_box_id_list', 0, $id));
        $result = $redis->executeCommand($redis_cmd);

        if ($result) {

            return new JsonResponse(
                [
                    'errorCode' => self::E_OK,
                ]
            );

        } else {

            return new JsonResponse(
                [
                    'errorCode' => self::E_OPERATE_FAIL,
                ]
            );
        }


    }


    /**
     * @Route("/cloud/box/record", methods="POST")
     */
    public function recordBoxAction(Request $req)
    {

        $id = $req->request->get('boxID');
        $action = $req->request->get('action');

        $redis = $this->container->get('snc_redis.default');

        $redis_cmd = $redis->createCommand('lrange', array('cloudBox-' . $action . '-' . $id, 0, 20));
        $data = $redis->executeCommand($redis_cmd);

        $action_list = [];
        foreach ($data as $val) {
            $v = json_decode($val, true);
            if ($action == 'mileage')
                $action_list[] = ['mileage' => $v['mileage'], 'time' => date('Y-m-d H:i:s', $v['time'])];

            if ($action == 'range')
                $action_list[] = ['range' => $v['range'], 'time' => date('Y-m-d H:i:s', $v['time'])];

            if ($action == 'gps')
                $action_list[] = ['gps' => $v['coordinate'][0] . '/' . $v['coordinate'][1], 'time' => date('Y-m-d H:i:s',
                    $v['time'])];
            if ($action == 'switch')
                $action_list[] = ['switch' => $v['status'], 'time' => date('Y-m-d H:i:s', $v['time'])];
            if ($action == 'Gsensor')
                $action_list[] = ['Gsensor' => $v['Gsensor'], 'time' => date('Y-m-d H:i:s', $v['time'])];
            if ($action == 'speed')
                $action_list[] = ['speed' => $v['speed'], 'time' => date('Y-m-d H:i:s', $v['time'])];
            if ($action == 'BusOff_Event')
                $action_list[] = ['BusOff_Event' => $v['BusOff_Event'], 'time' => date('Y-m-d H:i:s', $v['time'])];
            if ($action == 'Demolish_Event')
                $action_list[] = ['Demolish_Event' => $v['Demolish_Event'], 'time' => date('Y-m-d H:i:s', $v['time'])];
            if ($action == 'Passwd_Crack_Event')
                $action_list[] = ['Passwd_Crack_Event' => $v['Passwd_Crack_Event'], 'time' => date('Y-m-d H:i:s', $v['time'])];
            if ($action == 'SIM_Check_Event')
                $action_list[] = ['SIM_Check_Event' => $v['SIM_Check_Event'], 'time' => date('Y-m-d H:i:s', $v['time'])];
            if ($action == 'PKCE_Event')
                $action_list[] = ['PKCE_Event' => $v['PKCE_Event'], 'time' => date('Y-m-d H:i:s', $v['time'])];
            if ($action == 'MCU_808_Event')
                $action_list[] = ['MCU_808_Event' => $v['MCU_808_Event'], 'time' => date('Y-m-d H:i:s', $v['time'])];
            if ($action == 'GPRS_SIG_Conn_Event')
                $action_list[] = ['GPRS_SIG_Conn_Event' => $v['GPRS_SIG_Conn_Event'], 'time' => date('Y-m-d H:i:s', $v['time'])];
            if ($action == 'System_Voltage_Event')
                $action_list[] = ['System_Voltage_Event' => $v['System_Voltage_Event'], 'time' => date('Y-m-d H:i:s', $v['time'])];
            if ($action == 'PPP_Event')
                $action_list[] = ['PPP_Event' => $v['PPP_Event'], 'time' => date('Y-m-d H:i:s', $v['time'])];
            if ($action == 'Reset')
                $action_list[] = ['Reset' => $v['Reset'], 'time' => date('Y-m-d H:i:s', $v['time'])];
            if ($action == 'Lock_Failed_Event')
                $action_list[] = ['Lock_Failed_Event' => $v['Lock_Failed_Event'], 'time' => date('Y-m-d H:i:s', $v['time'])];
            if ($action == 'WakeUp_Event')
                $action_list[] = ['WakeUp_Event' => $v['WakeUp_Event'], 'time' => date('Y-m-d H:i:s', $v['time'])];
            if ($action == '200011')
                $action_list[] = ['200011' => $v['200011'], 'time' => date('Y-m-d H:i:s', $v['time'])];
            if ($action == '200012')
                $action_list[] = ['200012' => $v['200012'], 'time' => date('Y-m-d H:i:s', $v['time'])];
            if ($action == '200013')
                $action_list[] = ['200013' => $v['200013'], 'time' => date('Y-m-d H:i:s', $v['time'])];
            if ($action == '200014')
                $action_list[] = ['200014' => $v['200014'], 'time' => date('Y-m-d H:i:s', $v['time'])];
            if ($action == '200015')
                $action_list[] = ['200015' => $v['200015'], 'time' => date('Y-m-d H:i:s', $v['time'])];
            if ($action == '200016')
                $action_list[] = ['200016' => $v['200016'], 'time' => date('Y-m-d H:i:s', $v['time'])];
            if ($action == 'sw-version')
                $action_list[] = ['sw-version' => $v['sw_ver'], 'time' => date('Y-m-d H:i:s', $v['time'])];
            if ($action == 'Open_Door_Flag')
                $action_list[] = ['Open_Door_Flag' => $v['Open_Door_Flag'], 'time' => date('Y-m-d H:i:s', $v['time'])];


        }


        return new JsonResponse(
            [
                'errorCode' => self::E_OK,
                'data' => $action_list
            ]
        );

    }


    /**
     * @Route("/cloud/box/add", methods="POST")
     */
    public function addBoxAction(Request $req)
    {

        $id = $req->request->get('boxID');
        $redis = $this->container->get('snc_redis.default');

        $redis_cmd = $redis->createCommand('lpush', array('cloud_box_id_list', $id));
        $result = $redis->executeCommand($redis_cmd);

        if ($result) {

            return new JsonResponse(
                [
                    'errorCode' => self::E_OK,
                ]
            );

        } else {

            return new JsonResponse(
                [
                    'errorCode' => self::E_OPERATE_FAIL,
                ]
            );
        }


    }


    /**
     * @Route("/order", methods="POST",name="auto_api_rental_car_order")
     */
    public function orderAction(Request $req)
    {
        $cid = $req->request->getInt('rentalCarID');
        $uid = $req->request->get('userID');
        $source = $req->request->get('source');
        $rsid = $req->request->getInt('rentalStationID');
        $back_station_id = $req->request->getInt('returnStationID');

        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->findOneBy(['token' => $uid]);

        if (empty($member)) {
            return new JsonResponse([
                'errorCode' => self::E_NO_MEMBER,
                'errorMessage' => self::M_NO_MEMBER,
            ]);
        }


        $authMember = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:AuthMember')
            ->findOneBy(['member' => $member]);


        if (empty($authMember)) {

            return new JsonResponse([
                'errorCode' => self::E_MEMBER_NO_AUTH,
                'errorMessage' => self::M_MEMBER_NO_AUTH,
            ]);
        }


        $auth_status = $this->get('auto_manager.member_helper')->getStatus($authMember);

        if ($auth_status == \Auto\Bundle\ManagerBundle\Entity\AuthMember::AUTH_LICENSE_EXPIRE) {

            return new JsonResponse([
                'errorCode' => self::E_MEMBER_AUTH_EXPIRE,
                'errorMessage' => self::M_MEMBER_AUTH_EXPIRE,
            ]);

        }

        if ($auth_status == \Auto\Bundle\ManagerBundle\Entity\AuthMember::AUTH_FAILED) {

            return new JsonResponse([
                'errorCode' => self::E_MEMBER_AUTH_FAIL,
                'errorMessage' => self::M_MEMBER_AUTH_FAIL,
            ]);

        }

        if ($auth_status == \Auto\Bundle\ManagerBundle\Entity\AuthMember::UPDATED_NO_AUTH) {

            return new JsonResponse([
                'errorCode' => self::E_MEMBER_WAIT_AUTH,
                'errorMessage' => self::M_MEMBER_WAIT_AUTH
            ]);

        }
        if ($auth_status == \Auto\Bundle\ManagerBundle\Entity\AuthMember::NO_UPDATE_AUTH) {

            return new JsonResponse([
                'errorCode' => self::E_MEMBER_NO_AUTH,
                'errorMessage' => self::M_MEMBER_NO_AUTH
            ]);

        }

        $authMembers = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:AuthMember')
            ->findBy(['IDNumber' => $authMember->getIDNumber()]);

        $member_ids = [];

        foreach ($authMembers as $auth) {

            $member_ids[] = $auth->getMember()->getId();

        }


        if (!empty($member_ids)) {

            $qb =
                $this
                    ->getDoctrine()
                    ->getRepository('AutoManagerBundle:RentalOrder')
                    ->createQueryBuilder('b');

            $no_pay_order =
                $qb
                    ->select('b')
                    ->join('b.member', 'm')
                    ->where($qb->expr()->in('m.id', ':ids'))
                    ->andWhere($qb->expr()->isNull('b.payTime'))
                    ->andWhere($qb->expr()->isNull('b.cancelTime'))
                    ->setParameter('ids', $member_ids)
                    ->setMaxResults(1)
                    ->getQuery()
                    ->getOneOrNullResult();

            if (!empty($no_pay_order)) {

                $order_status = $this->get("auto_manager.order_helper")->get_order_status($no_pay_order);

                if ($no_pay_order->getMember() == $member) {
                    return new JsonResponse([
                        'errorCode' => self::E_HAS_ORDER,
                        'errorMessage' => '您有未完成行程',
                        'orderID' => $no_pay_order->getId(),
                        'status' => $order_status

                    ]);
                } else {
                    return new JsonResponse([
                        'errorCode' => self::E_OTHER_ACCOUNT_HAS_NO_PAY_ORDER,
                        'errorMessage' => '您使用' . $no_pay_order->getMember()->getMobile() . '手机号租车后有未完成行程，请完成后再租赁车辆。',
                        'orderID' => $no_pay_order->getId(),
                        'status' => $order_status

                    ]);
                }


            }

        }


        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:IllegalRecord')
                ->createQueryBuilder('i');

        $illegal_record =
            $qb
                ->select('i')
                ->join('i.order', 'o')
                ->join('o.member', 'm')
                ->where($qb->expr()->in('m.id', ':ids'))
                ->andWhere($qb->expr()->isNull('i.handleTime'))
                ->andWhere($qb->expr()->lte('i.createTime', ':time'))
                ->setParameter('time', (new \DateTime())->modify("-3 days"))
                ->setParameter('ids', $member_ids)
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();


        if (!empty($illegal_record)) {

            if ($illegal_record->getOrder()->getMember()->getId() == $uid) {
                return new JsonResponse([
                    'errorCode' => self::E_HAS_ILLEGAL_RECORD,
                    'errorMessage' => self::M_HAS_ILLEGAL_RECORD,
                ]);

            } else {
                return new JsonResponse([
                    'errorCode' => self::E_HAS_ILLEGAL_RECORD,
                    'errorMessage' => '您使用' . $illegal_record->getOrder()->getMember()->getMobile() . '手机号租车时产生违章，请处理完违章后再租赁车辆。',
                ]);
            }

        }

        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:BlackList')
                ->createQueryBuilder('b');

        $blacklist =
            $qb
                ->select('b')
                ->join('b.authMember', 'a')
                ->where($qb->expr()->eq('a.IDNumber', ':id'))
                ->andWhere($qb->expr()->orX(
                    $qb->expr()->gt('b.endTime', ':now'),
                    $qb->expr()->isNull('b.endTime')
                ))
                ->setParameter('id', $authMember->getIDNumber())
                ->setParameter('now', (new \DateTime()))
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();

        if (!empty($blacklist)) {

            $message = '';
            if ($blacklist->getReason() == 1)
                $message = '由于您的个人征信不良，您的驾驶证关联的所有手机将不能使用车辆租赁服务。有疑问请致电400-111-8220';

            if ($blacklist->getReason() == 2)
                $message = '由于您使用' . $blacklist->getAuthMember()->getMember()->getMobile() . '手机在使用我们的服务时严重违反用户协议，您的驾驶证关联的所有手机将不能使用车辆租赁服务。有疑问请致电400-111-8220';

            if ($blacklist->getReason() == 3)
                $message = '由于您使用' . $blacklist->getAuthMember()->getMember()->getMobile() . '手机在租赁过程中产生严重过失，您的驾驶证关联的所有手机将不能使用车辆租赁服务。有疑问请致电400-111-8220';


            return new JsonResponse([
                'errorCode' => self::E_MEMBER_IN_BLACKLIST,
                'errorMessage' => $message,
            ]);
        }


        $rental_car = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->find($cid);

        if (empty($rental_car)) {
            return new JsonResponse([
                'errorCode' => self::E_NO_RENTAL_CAR,
                'errorMessage' => self::M_NO_RENTAL_CAR,
            ]);
        }

        if (empty($rental_car->getOnline()) || $rental_car->getOnline()->getStatus() == 0) {
            return new JsonResponse([
                'errorCode' => self::E_RENTAL_CAR_OFFLINE,
                'errorMessage' => self::M_RENTAL_CAR_OFFLINE,
            ]);
        }

        $car_order = $this->get("auto_manager.order_helper")->get_progress_rental_order_by_car($rental_car);

        if (!empty($car_order)) {
            return new JsonResponse([
                'errorCode' => self::E_HAS_RENTAL_ORDER,
                'errorMessage' => self::M_HAS_RENTAL_ORDER,
                'message' => $car_order->getId()
            ]);
        }


        $illegal = $this->get("auto_manager.illegal_record_helper")->get_member_illegal_order($member);

        if (!empty($illegal)) {
            return new JsonResponse([
                'errorCode' => self::E_HAS_ILLEGAL_RECORD,
                'errorMessage' => self::M_HAS_ILLEGAL_RECORD,
            ]);
        }


        if ($back_station_id && $back_station_id != $rental_car->getRentalStation()->getId()) {
            $backRentalStation = $this->getDoctrine()
                ->getRepository('AutoManagerBundle:rentalStation')
                ->find($back_station_id);

            $count = $this->get("auto_manager.station_helper")->get_parking_space_count($backRentalStation);

            if ($count == 0) {
                return new JsonResponse([
                    'errorCode' => self::E_STATION_NO_PARKING_SPACE,
                    'errorMessage' => self::M_STATION_NO_PARKING_SPACE,
                ]);

            }


        } else {
            $backRentalStation = $rental_car->getRentalStation();
        }


        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:Station')
                ->createQueryBuilder('s');

        $rental_station =
            $qb
                ->select('s')
                ->where($qb->expr()->eq('s.id', ':stationID'))
                ->setParameter('stationID', $rsid)
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();

        if (!empty($rental_station)) {

            $detail = call_user_func($this->get("auto_manager.station_helper")->get_station_normalizer(), $rental_station);

            // 2017-03-09 禁用押金
            // if ($detail['city'] == "三亚市") {
            if (false) {

                $qb =
                    $this
                        ->getDoctrine()
                        ->getRepository('AutoManagerBundle:DepositArea')
                        ->createQueryBuilder('s');

                $deposit =
                    $qb
                        ->select('s')
                        ->where($qb->expr()->eq('s.area', ':areaId'))
                        ->setParameter('areaId', $detail['areaID'])
                        ->setMaxResults(1)
                        ->getQuery()
                        ->getOneOrNullResult();

                if (!empty($deposit)) {
                    $deposit_info = call_user_func($this->get("auto_manager.deposit_helper")->get_deposit_area_normalizer(), $deposit);

                    if ($deposit_info['isneed']) {
                        //
                        $qb =
                            $this
                                ->getDoctrine()
                                ->getRepository('AutoManagerBundle:Deposit')
                                ->createQueryBuilder('s');

                        $depositinfo =
                            $qb
                                ->select('s')
                                ->where($qb->expr()->eq('s.member', ':member'))
                                ->andWhere($qb->expr()->gte('s.total', ':amount'))
                                ->setParameter('member', $member)
                                ->setParameter('amount', $deposit_info['amount'])
                                ->setMaxResults(1)
                                ->getQuery()
                                ->getOneOrNullResult();

                        if (empty($depositinfo)) {
                            return new JsonResponse([

                                'errorCode' => self::E_NEED_PAY_DEPOSIT,
                                'errorMessage' => '您需要支付押金',

                            ]);
                        }
                    }
                }
            }

            // 深圳余额
            if ($detail['city'] == "深圳市") {

                if ($member->getWallet() == null || $member->getWallet() < 500) {

                    return new JsonResponse([
                        'errorCode' => self::E_NEED_MORE_MOMERY,
                        'errorMessage' => self::M_NEED_MORE_MOMERY,
                    ]);
                }
            }
        }


        $order = new \Auto\Bundle\ManagerBundle\Entity\RentalOrder();
        $order->setRentalCar($rental_car);
        $order->setMember($member);
        $order->setPickUpStation($rental_car->getRentalStation());
        $order->setReturnStation($backRentalStation);
        if ($order->getRentalCar()->getRentalStation()->getBackType()
            == \Auto\Bundle\ManagerBundle\Entity\RentalCar::SAME_PLACE_BACK
        ) {
            $order->setReturnStation($rental_car->getRentalStation());
        }

        if ($source) {
            $order->setSource($source);
        }

        $redis = $this->container->get('snc_redis.default');
        $redis_cmd = $redis->createCommand('GET', array('api-rental-car-' . $rental_car->getId() . "-order"));

        $mark = 1;
        while ($redis->executeCommand($redis_cmd) == 0 && $mark <= 6) {

            sleep(1);
            $mark++;

        }

        if ($mark == 6) {
            return new JsonResponse([
                'errorCode' => self::E_HAS_RENTAL_ORDER,
                'errorMessage' => self::M_HAS_RENTAL_ORDER,
            ]);
        }


        $redis_cmd = $redis->createCommand('SET', array('api-rental-car-' . $rental_car->getId() . "-order", "0"));
        $redis->executeCommand($redis_cmd);


        //检查车是否已有订单
        $car_order = $this->get("auto_manager.order_helper")->get_progress_rental_order_by_car($rental_car);

        if (!empty($car_order)) {
            return new JsonResponse([
                'errorCode' => self::E_HAS_RENTAL_ORDER,
                'errorMessage' => self::M_HAS_RENTAL_ORDER,
            ]);
        }

        $man = $this->getDoctrine()->getManager();

        $man->persist($order);
        $man->flush();


        $redis_cmd = $redis->createCommand('SET', array('api-rental-car-' . $rental_car->getId() . "-order", "1"));
        $redis->executeCommand($redis_cmd);

        $dispatch = new \Auto\Bundle\ManagerBundle\Entity\DispatchRentalCar();
        $dispatch->setKind(\Auto\Bundle\ManagerBundle\Entity\DispatchRentalCar::USER_RETURN_CAR_KIND);
        $dispatch->setRentalCar($order->getRentalCar());
        $dispatch->setRentalStation($backRentalStation);
        $dispatch->setRentalOrder($order);
        $dispatch->setStatus(0);

        $order->getRentalCar()->setRentalStation($backRentalStation);
        $man->persist($rental_car);
        $man->flush();

        $man->persist($dispatch);
        $man->flush();

        $this->get("auto_manager.sms_helper")->rentalSMS(

            $order->getMember()->getMobile(),
            $order->getRentalCar()->getLicense(),
            $order->getPickUpStation()->getName()

        );


        return new JsonResponse([
            'errorCode' => self::E_OK,
            'order' => call_user_func($this->get("auto_manager.order_helper")->get_rental_order_normalizer(), $order),
        ]);

    }

    /**
     * @Route("/inUse", methods="POST")
     */
    public function inUseAction(Request $req)
    {

        $uid = $req->request->get('userID');

        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->findOneBy(['token' => $uid]);

        if (empty($member)) {
            return new JsonResponse([
                'errorCode' => self::E_NO_MEMBER,
                'errorMessage' => self::M_NO_MEMBER,
            ]);
        }

        $order = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalOrder')
            ->findOneBy(['member' => $member, 'endTime' => null], ['id' => 'desc']);

        if (empty($order)) {
            return new JsonResponse([
                'errorCode' => self::E_NO_ORDER,
                'errorMessage' => self::M_NO_ORDER,
            ]);
        }

        return new JsonResponse([
            'errorCode' => self::E_OK,
            'order' => call_user_func($this->get('auto_manager.order_helper')->get_rental_order_normalizer(),
                $order)
        ]);

    }


    /**
     * @Route("/getCost", methods="POST")
     */
    public function getCostAction(Request $req)
    {
        $day = $req->request->get('day');
        $hour = $req->request->get('hour');
        $minute = $req->request->get('minute');
        $id = $req->request->get('rentalID');

        $time = $day . ' ' . $hour . ':' . $minute . ':00';

        $time = date('Y-m-d H:i:s', strtotime($time));
        $order = new \Auto\Bundle\ManagerBundle\Entity\RentalOrder();

        $rental_car = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->find($id);
        $order->setUseTime(new \DateTime());
        $order->setEndTime(new \DateTime($time));
        $order->setRentalCar($rental_car);

        $rentalPrice = $this->get('auto_manager.order_helper')->get_rental_order_cost($order);

        return new JsonResponse([
            'errorCode' => self::E_OK,
            'dateTime' => $time,
            'rentalPrice' => $rentalPrice
        ]);

    }

    /**
     * @Route("/online", methods="POST")
     */

    public function onlineAction(Request $req)
    {

        $uid = $req->request->get('userID');
        $rental_car_id = $req->request->get('rentalCarID');
        $reason = $req->request->get('reason');
        $remark = $req->request->get('remark');
        $status = $req->request->getInt('status');

        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->findOneBy(['token' => $uid]);

        $this->checkOperator($member);

        $rentalCar = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->find($rental_car_id);

        $this->checkRentalCar($rentalCar);

        $onlineRecord = new \Auto\Bundle\ManagerBundle\Entity\RentalCarOnlineRecord();

        $onlineRecord->setRentalCar($rentalCar);
        $onlineRecord->setStatus($status);
        $onlineRecord->setMember($member);
        if ($reason) $onlineRecord->setReason(json_decode($reason, true));
        if ($remark) $onlineRecord->setRemark($remark);

        $man = $this->getDoctrine()->getManager();
        $man->persist($onlineRecord);
        $man->flush();

        $rentalCar->setOnline($onlineRecord);
        $man->persist($rentalCar);
        $man->flush();

        $carPartnerData = [
            'carId' => $rental_car_id,
            'operator' => $member->getId(),
            'reason' => '',
            'remark' => '',
            'status' => $status,
            'stationId' => $rentalCar->getRentalStation()->getId(),
        ];
        $this->get("auto_manager.partner_helper")->carOnOffLine($carPartnerData);

        return new JsonResponse([
            'errorCode' => self::E_OK
        ]);

    }


    /**
     * @Route("/operate", methods="POST")
     */

    public function operateDoorAction(Request $req)
    {


        $uid = $req->request->get('userID');
        $rental_car_id = $req->request->get('rentalCarID');
        $operate = $req->request->get('operate');

        $rentalCar = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->find($rental_car_id);

        if (empty($rentalCar->getBoxId())) {
            return new JsonResponse([
                'errorCode' => self::E_NO_CAR_START_DEVICE,
                'errorMessage' => self::M_NO_CAR_START_DEVICE
            ]);
        }

        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->findOneBy(['token' => $uid]);

        $error = $this->checkMember($member);

        if (!empty($error)) {
            return new JsonResponse($error);
        }

        if (!(in_array('ROLE_ADMIN', $member->getRoles()) || in_array('ROLE_OPERATE', $member->getRoles()) || in_array('ROLE_SERVER', $member->getRoles()))) {

            return new JsonResponse([
                'errorCode' => self::E_NO_RIGHT,
                'errorMessage' => self::M_NO_RIGHT
            ]);

        }

        if ($operate) {
            $result = $this->get("auto_manager.rental_car_helper")->operate($rentalCar, $operate, $member, '');

            if ($result) {
                return new JsonResponse([
                    'errorCode' => self::E_OK,
                ]);
            } else {
                return new JsonResponse([
                    'errorCode' => self::E_OPERATE_FAIL,
                    'errorMessage' => self::M_OPERATE_FAIL
                ]);
            }


        } else {
            return new JsonResponse([
                'errorCode' => self::E_OPERATE_FAIL,
                'errorMessage' => self::M_OPERATE_FAIL
            ]);
        }


    }

    /**
     * @Route("/lock", methods="POST")
     */

    public function lockDoorAction(Request $req)
    {

        $uid = $req->request->get('userID');
        $rental_car_id = $req->request->get('rentalCarID');
        $status = $req->request->getInt('status');

        $operate = $status == 1 ? "close" : "open";

        $rentalCar = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->find($rental_car_id);

        if (empty($rentalCar->getBoxId())) {
            return new JsonResponse([
                'errorCode' => self::E_NO_CAR_START_DEVICE,
                'errorMessage' => self::M_NO_CAR_START_DEVICE
            ]);
        }

        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->findOneBy(['token' => $uid]);

        if (empty($member)) {

            return new JsonResponse([
                'errorCode' => self::E_NO_MEMBER,
                'errorMessage' => self::M_NO_MEMBER
            ]);

        }

        if (!(in_array('ROLE_ADMIN', $member->getRoles()) || in_array('ROLE_OPERATE', $member->getRoles()) || in_array('ROLE_SERVER', $member->getRoles()))) {

            return new JsonResponse([
                'errorCode' => self::E_NO_RIGHT,
                'errorMessage' => self::M_NO_RIGHT
            ]);

        }


        $result =
            $this->get("auto_manager.rental_car_helper")->operate($rentalCar, $operate, $member, '');

        if ($result) {
            return new JsonResponse([
                'errorCode' => self::E_OK,
            ]);
        } else {
            return new JsonResponse([
                'errorCode' => self::E_OPERATE_FAIL,
                'errorMessage' => self::M_OPERATE_FAIL
            ]);
        }


    }


    /**
     * @Route("/curLocation", methods="POST")
     */

    public function carCurrentLocationAction(Request $req)
    {


        $carType = $req->request->getInt('carType');
        $city = $req->request->getInt('city');
        $province = $req->request->getInt('province');
        $stationId = $req->request->getInt('stationID');


        // 初始化
        $redis = $this->container->get('snc_redis.default');

        $onlineList = [];  // 在线车辆
        $offlineList = [];  // 离线车辆
        $resultList = [];  // 筛选结果


        // 车型
        if ($carType != self::UNSELECTED) {

            if ($stationId) {

                // 车型与租赁点的交集
                $redis_cmd = $redis->createCommand('sinter', array("location-car-type-" . $carType, "location-station-car-" . $stationId));
                $resultList = $redis->executeCommand($redis_cmd);

                $carNum = count($resultList);

            } elseif ($city) {

                // 车型与城市的交集
                $redis_cmd = $redis->createCommand('sinter', array("location-car-type-" . $carType, "location-city-car-" . $city));
                $resultList = $redis->executeCommand($redis_cmd);

                $carNum = count($resultList);

            } elseif ($province) {

                // 清redis缓存
                $redis_cmd = $redis->createCommand('smembers', array("location-province-car-" . $province));
                $provinceCarList = $redis->executeCommand($redis_cmd);

                if ($provinceCarList) {

                    $redis_cmd = $redis->createCommand('srem', array("location-province-car-" . $province, $provinceCarList));
                    $redis->executeCommand($redis_cmd);
                }


                // 获取该省城市列表
                $citys =
                    $this
                        ->getDoctrine()
                        ->getRepository('AutoManagerBundle:Area')
                        ->findBy(['parent' => $province]);

                $citykeys = [];
                foreach ($citys as $v) {

                    $citykeys [] = "location-city-car-" . $v->getId();
                }

                // 城市的并集
                $redis_cmd = $redis->createCommand('sunionstore', array("location-province-car-" . $province, $citykeys));
                $redis->executeCommand($redis_cmd);

                // 车型与省的交集
                $redis_cmd = $redis->createCommand('sinter', array("location-province-car-" . $province, "location-car-type-" . $carType));
                $resultList = $redis->executeCommand($redis_cmd);

                $carNum = count($resultList);
            }

        } else if ($city != null || $province != null || $stationId != null) {

            if ($stationId) {

                // 车型与租赁点的交集
                $redis_cmd = $redis->createCommand('smembers', array("location-station-car-" . $stationId));
                $resultList = $redis->executeCommand($redis_cmd);

            } elseif ($city) {

                // 车型与城市的交集
                $redis_cmd = $redis->createCommand('smembers', array("location-city-car-" . $city));
                $resultList = $redis->executeCommand($redis_cmd);

            } elseif ($province) {


                $citys =
                    $this
                        ->getDoctrine()
                        ->getRepository('AutoManagerBundle:Area')
                        ->findBy(['parent' => $province]);

                $citykeys = [];
                foreach ($citys as $v) {

                    $citykeys [] = "location-city-car-" . $v->getId();
                }


                // 城市的并集
                $redis_cmd = $redis->createCommand('sunion', array($citykeys));
                $resultList = $redis->executeCommand($redis_cmd);

                $carNum = count($resultList);

            }
        } else {

            $redis_cmd = $redis->createCommand('hkeys', array("feeZu-car-curlocation"));
            $resultList = $redis->executeCommand($redis_cmd);

            $carNum = count($resultList);
        }

        // 获取当前已定位车数量
        // $redis_cmd = $redis->createCommand('hlen',array("feeZu-car-curlocation"));
        // $carNum = $redis->executeCommand($redis_cmd);


        if ($carNum > 0) {

            if (!empty($resultList)) {

                sort($resultList);

                // 获取车辆gps定位
                $redis_cmd = $redis->createCommand('hmget', array("feeZu-car-curlocation", $resultList));
                $gpsLists = $redis->executeCommand($redis_cmd);

                // 获取车辆上线状态
                $redis_cmd = $redis->createCommand('hmget', array("feeZu-car-online-status", $resultList));
                $carStatusLists = $redis->executeCommand($redis_cmd);

                if ($carStatusLists) {

                    $cur = 0;

                    foreach ($carStatusLists as $status) {

                        $gpsList = json_decode($gpsLists[$cur]);

                        // 在线
                        if ($status !== "1") {
                            $onlineList[] = [
                                'lon' => $gpsList[0],
                                'lat' => $gpsList[1],
                                'license' => $gpsList[2],
                                'time' => $gpsList[3],
                            ];
                        } else {
                            $offlineList[] = [
                                'lon' => $gpsList[0],
                                'lat' => $gpsList[1],
                                'license' => $gpsList[2],
                                'time' => $gpsList[3],
                            ];
                        }

                        $cur++;
                    }
                }
            }
        }

        return new JsonResponse([
            'errorCode' => self::E_OK,
            'onlinelist' => $onlineList,
            'offlinelist' => $offlineList,
            'reportTime' => (new \DateTime())->format('Y/m/d H:i')
        ]);
    }


    /**
     * @Route("/car/detail", methods="POST",name="auto_api_rental_car_detail")
     */
    public function getCarDetail(Request $req)
    {

        $licenseplate = $req->request->get('licenseplate');

        // 输入参数为null
        if (empty($licenseplate)) {

            return new JsonResponse([
                'errorCode' => self::E_WRONG_PARAMETER,
                'errorMessage' => self::M_WRONG_PARAMETER,

            ]);
        }


        // 初始化
        $redis = $this->container->get('snc_redis.default');

        // 车牌
        $licenseplate = strtoupper($licenseplate);
        preg_match("/([A-Z0-9]){6}/", $licenseplate, $matches);

        $onlineStatus = "";
        $remainMileage = "";
        $reportTime = "";
        $lon = "";
        $lan = "";
        $result = [];

        if ($matches[0]) {

            $car =
                $this
                    ->getDoctrine()
                    ->getRepository('AutoManagerBundle:RentalCar')
                    ->findOneBy(['licensePlate' => $matches[0]]);

            if ($car) {

                // 剩余里程
                $redis_cmd = $redis->createCommand('lindex', array("feeZu-range-" . $car->getBoxId(), 0));
                $remainMileage = $redis->executeCommand($redis_cmd);

                if ($remainMileage) {

                    $detail = json_decode($remainMileage);
                    $remainMileage = $detail->range;

                    // 更新时间
                    $reportTime = date('Y-m-d H:i', $detail->time);
                } else {

                    $carStatus = $this->get('auto_manager.fee_zu_helper')->oneCarStatus($car->getBoxId());

                    if ($carStatus) {

                        $remainMileage = $carStatus['remainMileage'];
                        $reportTime = $carStatus['reportTime'];
                    }
                }


                // gps
                $redis_cmd = $redis->createCommand('hget', array("feeZu-car-curlocation", $car->getBoxId()));
                $gpsData = $redis->executeCommand($redis_cmd);

                // if ($gpsData) {
                if (!$gpsData) {
                    $detail = json_decode($gpsData);

                    $lon = $detail[0];
                    $lan = $detail[1];
                } else {

                    $gpsData = $this->get('auto_manager.fee_zu_helper')->findCarLocation($car->getBoxId());

                    if ($gpsData) {

                        // dump($gpsData);exit;
                        $lon = $gpsData['longitude'];
                        $lan = $gpsData['latitude'];
                    }

                }

            }

            // 车辆状态
            $carStatus = $this->get('auto_manager.rental_car_helper')->get_rental_car_status($car);

            // 设备状态
            $redis_cmd = $redis->createCommand('hget', array("feeZu-car-online-status", $car->getBoxId()));
            $onlineStatus = $redis->executeCommand($redis_cmd);
        }


        return new JsonResponse([
            'errorCode' => self::E_OK,
            'remainMileage' => $remainMileage,
            'carStatus' => $carStatus,
            'onlineStatus' => $onlineStatus,
            'longitude' => $lon,
            'latitude' => $lan,
            'reportTime' => $reportTime,

        ]);


    }
}
