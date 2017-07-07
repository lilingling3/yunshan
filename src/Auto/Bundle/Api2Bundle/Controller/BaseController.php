<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 16/5/24
 * Time: 下午1:57
 */

namespace Auto\Bundle\Api2Bundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

class BaseController extends Controller{

    const E_OK = 0;

    //城市地区 area
    const E_NO_AREA = -10001;

    //站点 station
    const E_NO_STATION = -20001;
    const M_NO_STATION = "没有该站点";
    const E_STATION_NO_PARKING_SPACE = -20002;
    const M_STATION_NO_PARKING_SPACE = "租赁地点停车位已满";
    const E_NO_STATION_NEARBY = -20003;
    const M_NO_STATION_NEARBY = "附近5公里内没有租赁点，请查看其它区域租赁点。";

    //租赁车辆 rental car
    const E_NO_RENTAL_CAR = -30001;
    const M_NO_RENTAL_CAR = "没有租赁车辆";
    //租赁车辆 rental car
    const E_RENTAL_CAR_OFFLINE = -30002;
    const M_RENTAL_CAR_OFFLINE = "租赁车辆已下线";
    const E_HAS_RENTAL_ORDER = -30003;
    const M_HAS_RENTAL_ORDER = "车辆被租赁,请选择其他车辆";
    const E_STATION_CAR_DISTANCE = -30004;
    const M_STATION_CAR_DISTANCE = "租赁车辆与租赁点相距太远";
    //用户
    const E_NO_MEMBER = -40001;
    const M_NO_MEMBER = "手机号或密码错误";
    const E_MEMBER_REGISTERED = -40002;
    const M_MEMBER_REGISTERED = "用户已存在";
    const E_VERIFY_CODE_WRONG = -40003;
    const M_VERIFY_CODE_WRONG = "验证码错误";
    const E_VERIFY_MOBILE_WRONG = -40004;
    const M_VERIFY_MOBILE_WRONG = "验证码与手机号不匹配";
    const E_PASSWORD_WRONG = -40005;
    const M_PASSWORD_WRONG = "手机号或密码错误";
    const E_INVALID_CODE = -40006;
    const M_INVALID_CODE = "验证码已过期";
    const E_LOGIN_OTHER_DEVICE = -40007;
    const M_LOGIN_OTHER_DEVICE = "已在其他设备登录";
    const E_MEMBER_IN_BLACKLIST = -40008;
    const M_MEMBER_IN_BLACKLIST = "用户在黑名单列表内";
    const E_MEMBER_NO_AUTH = -40009;
    const M_MEMBER_NO_AUTH = "租赁前请您先进行实名认证";
    const E_MEMBER_WAIT_AUTH = -40010;
    const M_MEMBER_WAIT_AUTH = "您的认证信息还未通过审核，请耐心等待！";
    const E_MEMBER_AUTH_FAIL = -40011;
    const M_MEMBER_AUTH_FAIL = "您的实名认证未通过，请重新认证。";
    const E_MEMBER_AUTH_EXPIRE = -40012;
    const M_MEMBER_AUTH_EXPIRE = "您的证件已过期，请更新证件后重新上传驾驶证!";
    const E_WRONG_MOBILE = -40013;
    const M_WRONG_MOBILE = "请输入正确手机号";


    //订单
    const E_NO_ORDER = -50001;
    const M_NO_ORDER = "没有该订单";
    const E_HAS_ORDER = -50002;
    const M_HAS_ORDER = "有未结算订单";
    const E_ORDER_END = -50003;
    const M_ORDER_END = "车辆已还";
    const E_ORDER_PAYED = -50004;
    const M_ORDER_PAYED = "订单已付款";
    const E_ORDER_PROGRESS = -50005;
    const M_ORDER_PROGRESS = "订单已开始进行";
    const E_CANCELED_ORDER = -50006;
    const M_CANCELED_ORDER = "订单已取消";
    const E_NO_PAY_END_ORDER = -50007;
    const M_NO_PAY_END_ORDER = "请先缴纳上次的车辆租赁费用";
    const E_CANCEL_ORDER_COUNT = -50008;
    const M_CANCEL_ORDER_COUNT = "订单取消次数过多";
    const E_FREE_ORDER_PAY_AMOUNT = -50009;
    const M_FREE_ORDER_PAY_AMOUNT = "0金额付款金额错误";
    const E_NOT_END_ORDER = -50010;
    const M_NOT_END_ORDER = "订单未还车";
    const E_UNABLE_CHANGE_STATION = -50011;
    const M_UNABLE_CHANGE_STATION = "不能更改还车点";
    const E_OTHER_ACCOUNT_HAS_NO_PAY_ORDER = -50012;
    const M_OTHER_ACCOUNT_HAS_NO_PAY_ORDER = "其他账号有未付款订单";
    const E_OUT_CANCEL_FREE_TIME = -50013;
    const M_OUT_CANCEL_FREE_TIME = "您已超出每天免费取消时间,取消行程将收取租赁费用";

    //违章纪录
    const E_NO_ILLEGAL_RECORD = -60001;
    const M_NO_ILLEGAL_RECORD = "没有该违章记录";
    const E_HAS_ILLEGAL_RECORD = -60002;
    const M_HAS_ILLEGAL_RECORD = "您还有违章未处理,请先处理完违章后再租赁车辆";

    //优惠券
    const E_NO_COUPON = -70001;
    const M_NO_COUPON = "无效优惠券";

    const E_NO_COUPON_CODE = -70002;
    const M_NO_COUPON_CODE = "无效兑换码";

    const E_USED_COUPON_CODE = -70003;
    const M_USED_COUPON_CODE = "该兑换码已被使用";

    const E_EXPIRE_COUPON = -70004;
    const M_EXPIRE_COUPON = "已过期";

    const E_HAS_TAKEN_COUPON = -70005;
    const M_HAS_TAKEN_COUPON = "优惠券已被领取过";

    const E_NO_COUPON_ACTIVITY = -70006;
    const M_NO_COUPON_ACTIVITY = "无效优惠活动";

    const E_NO_ACTIVITY_COUPON = -70007;
    const M_NO_ACTIVITY_COUPON = "优惠券已领完";


    const E_NO_RIGHT_GET_COUPON = -70008;
    const M_NO_RIGHT_GET_COUPON = "无权领取";

    //投票
    const E_NO_VOTE = -80001;
    const M_NO_VOTE = "无效投票";
    const E_NO_VOTE_OPTION = -80002;
    const M_NO_VOTE_OPTION = "无效投票选项";

    //车载设备
    const E_OPEN_DOOR = -90001;
    const M_OPEN_DOOR = "开门失败";
    const E_CLOSE_DOOR = -90002;
    const M_CLOSE_DOOR = "锁车失败";
    const E_FIND_CAR = -90003;
    const M_FIND_CAR = "寻车失败";
    const E_NO_CAR_START_DEVICE = -90004;
    const M_NO_CAR_START_DEVICE = "没有设备";
    const E_NO_RIGHT = -90005;
    const M_NO_RIGHT = "无权限操作";
    const E_OPERATE_FAIL = -90006;
    const M_OPERATE_FAIL = "操作错误";
    const E_ON_POWER = -90007;
    const M_ON_POWER = "通电失败";
    const E_OFF_POWER= -90008;
    const M_OFF_POWER = "断电失败";
    const E_ON_FIRE= -90009;
    const M_ON_FIRE = "没有熄火";
    const E_ON_NETWORK= -90010;
    const M_ON_NETWORK = "网络超时,请重试";



    //参数错误
    const E_WRONG_PARAMETER = -100001;
    const M_WRONG_PARAMETER = "参数错误";

    //没有广告
    const E_NO_AD = -110001;
    const M_NO_AD = "没有广告";

    //运营
    const E_NO_OPERATOR = -120001;
    const M_NO_OPERATOR = "没有该运营人员";

    //用车提醒
    const E_NO_REMIND = -130001;
    const M_NO_REMIND = "没有该用车提醒";

    //发票
    const E_NO_INVOICE = -140001;
    const M_NO_INVOICE = "没有发票";
    //充值
    const E_NO_RECHARGE_ORDER = -150001;
    const E_NO_RECHARGE_ACTIVITY = -150002;
    const M_NO_RECHARGE_ORDER = "没有充值信息";
    const M_NO_RECHARGE_ACTIVITY = "没有充值活动信息";

    //数据访问签名验证 authentication
    const E_NO_CHECKING_SIGN=-160001;
    const M_NO_CHECKING_SIGN="签名验证失败";
    const E_VISIT_EXPIRE=-160002;
    const M_VISIT_EXPIRE="访问过期";

    //缴费
    const E_HAVE_PAYMENT_ORDER = -170001;
    const M_HAVE_PAYMENT_ORDER = "您有新的缴费信息，请先处理完缴费再租赁车辆。";

    //押金
    const E_NEED_PAY_DEPOSIT = -180001;
    const M_NEED_PAY_DEPOSIT = "您需要支付押金";
    const E_HAS_PAY_DEPOSIT  = -180002;
    const M_HAS_PAY_DEPOSIT  = "您已经支付押金";
    const E_NEED_PAY_MORE_DEPOSIT = -180003;
    const M_NEED_PAY_MORE_DEPOSIT = "您支付的押金不足";
    const E_NO_DEPOSIT_ORDER = -180004;
    const M_NO_DEPOSIT_ORDER = "无法退款";

    //七牛上传
    const E_NO_BUCKET_TRANSMIT = -190001;
    const M_NO_BUCKET_TRANSMIT = "没有设置bucket";
    const E_NO_DATA_IN_DATABASE = -190002;
    const M_NO_DATA_IN_DATABASE = "数据库和redis中均无数据";
    const E_NO_THIS_BUCKET = -190003;
    const M_NO_THIS_BUCKET = "无此bucket";

    //check Member
    protected function checkMember(\Auto\Bundle\ManagerBundle\Entity\Member $member=null){

        if(empty($member)){

        return [
            'errorCode'    =>  self::E_NO_MEMBER,
            'errorMessage' =>  self::M_NO_MEMBER
        ];

        }
    }

    //check RentalCar
    protected function checkRentalCar(\Auto\Bundle\ManagerBundle\Entity\RentalCar $rentalCar=null){

        if(empty($rental_car)){
            return [
                'errorCode' =>  self::E_NO_RENTAL_CAR,
                'errorMessage' =>self::M_NO_RENTAL_CAR,
            ];
        }
    }


    //check RentalOrder
    protected function checkRentalOrder(\Auto\Bundle\ManagerBundle\Entity\RentalOrder $rentalOrder=null){
        if(empty($rentalOrder)){
            return [
                'errorCode' =>  self::E_NO_ORDER,
                'errorMessage' =>self::M_NO_ORDER,
            ];
        }

    }

    //check 运营人员
    protected function checkOperator(\Auto\Bundle\ManagerBundle\Entity\Member $member=null){

        $check_member = $this->checkMember($member);
        if(!empty($check_member)) return $check_member;

        if(!in_array('ROLE_OPERATE',$member->getRoles())){

            return [
                'errorCode'    =>  self::E_NO_RIGHT,
                'errorMessage' =>  self::M_NO_RIGHT
            ];

        }
    }

    //check 用户订单
    protected function checkMemberRentalOrder(\Auto\Bundle\ManagerBundle\Entity\Member $member=null,
                                              \Auto\Bundle\ManagerBundle\Entity\RentalOrder $order=null){
        $check_member = $this->checkMember($member);
        if(!empty($check_member)) return $check_member;

        $check_rental_order = $this->checkRentalOrder($order);
        if(!empty($check_rental_order)) return $check_rental_order;

        if($order->getMember()!=$member){

            return [
                'errorCode' =>  self::E_NO_ORDER,
                'errorMessage' =>self::M_NO_ORDER,
            ];

        }

    }

    //check 优惠活动

    protected function checkCouponActivity(\Auto\Bundle\ManagerBundle\Entity\CouponActivity $activity=null){

        if(empty($activity))
        {
            return [
                'errorCode' =>  self::E_NO_COUPON_ACTIVITY,
                'errorMessage' =>self::M_NO_COUPON_ACTIVITY,
            ];
        }

    }

    //检查是否异地登录
    protected function checkUserLogin($deviceToken,\Auto\Bundle\ManagerBundle\Entity\Member $member=null){

            $qb =
                $this
                    ->getDoctrine()
                    ->getRepository('AutoManagerBundle:MobileDevice')
                    ->createQueryBuilder('d');

            $device = $qb
                ->select('d')
                ->where($qb->expr()->eq('d.member', ':member'))
                ->andWhere($qb->expr()->eq('d.kind', ':kind'))
                ->setParameter('member', $member)
                ->setParameter('kind', \Auto\Bundle\ManagerBundle\Entity\MobileDevice::MOBILE_DEVICE_KIND_LOGIN)
                ->orderBy('d.createTime', 'DESC')
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();

            if(!empty($device)&&$device->getDeviceToken()!=$deviceToken){
                $message = "您的账号于".$device->getCreateTime()->format('Y-m-d H:i')."在另一个手机端登录。如非本人操作，则密码有可能泄露，请及时修改密码。";
                return new JsonResponse([
                    'errorCode'    =>  self::E_LOGIN_OTHER_DEVICE,
                    'errorMessage' => $message
                ]);
            }


    }



}