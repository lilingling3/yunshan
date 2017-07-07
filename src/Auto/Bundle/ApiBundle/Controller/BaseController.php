<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 15/8/11
 * Time: 下午5:49
 */

namespace Auto\Bundle\ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;


class BaseController extends Controller
{
    const E_OK = 0;
    const M_OK = 'Success';

    //城市地区 area
    const E_NO_AREA = -10001;

    //站点 station
    const E_NO_STATION = -20001;
    const M_NO_STATION = "没有该站点";
    const E_STATION_NO_PARKING_SPACE = -20002;
    const M_STATION_NO_PARKING_SPACE = "租赁地点停车位已满";

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
    const M_MEMBER_NO_AUTH = "未认证";
    const E_MEMBER_WAIT_AUTH = -40010;
    const M_MEMBER_WAIT_AUTH = "待审核";
    const E_MEMBER_AUTH_FAIL = -40011;
    const M_MEMBER_AUTH_FAIL = "认证失败";
    const E_MEMBER_AUTH_EXPIRE = -40012;
    const M_MEMBER_AUTH_EXPIRE = "认证过期";
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

    //押金
    const E_NEED_PAY_DEPOSIT = -180001;
    const M_NEED_PAY_DEPOSIT = "您需要支付押金";
    const E_HAS_PAY_DEPOSIT  = -180002;
    const M_HAS_PAY_DEPOSIT  = "您已经支付押金";
    const E_NEED_PAY_MORE_DEPOSIT = -180003;
    const M_NEED_PAY_MORE_DEPOSIT = "您支付的押金不足";
    const E_NO_DEPOSIT_ORDER = -180004;
    const M_NO_DEPOSIT_ORDER = "无法退款";
    const E_NEED_MORE_MOMERY = -180005;
    const M_NEED_MORE_MOMERY = "您的余额不足500请先充值";

    // 保险
    const E_NO_INSURANCE_EXIST = -190001;
    const M_NO_INSURANCE_EXIST = "无效的分时保险单";

    // 好友邀请
    const E_NO_VALID_INVITION = -200001;
    const M_NO_VALID_INVITION = "无有效的邀请";
    const E_MOBILE_HAS_INVITED = -200002;
    const M_MOBILE_HAS_INVITED = "此手机号已被邀请";
    const E_UNSAFE_LINK = -200003;
    const M_UNSAFE_LINK = "分享链接有误";
    const E_NO_INVITE_YOURSELF = -200004;
    const M_NO_INVITE_YOURSELF = "不能邀请自己哦，亲";
    //七牛上传图片
    const E_NO_IMAGELABEL = -210001;
    const M_NO_IMAGELABEL = "无订单号";
    const E_NO_QUESTIOM = -210002;
    const M_NO_QUESTIOM = "无提交问题";
    const E_NO_BUCKET = -210003;
    const M_NO_BUCKET = "无空间名";
    const E_NO_FILENAME = -210004;
    const M_NO_FILENAME = "无文件名";
    const E_CANNOT_NEW_QINIUIMAGE = -210005;
    const M_CANNOT_NEW_QINIUIMAGE = "初始化QiniuImage失败";
    const E_CANNOT_NEW_CARPROBLEM = -210006;
    const M_CANNOT_NEW_CARPROBLEM = "初始化CarProblem失败";
    const E_NO_DATA_IN_QINIUIMAGE = -210007;
    const M_NO_DATA_IN_QINIUIMAGE = "无图片对应数据";
    const E_NO_ID_IN_GETUPTOKEN = -210008;
    const M_NO_ID_IN_GETUPTOKEN = "请求token时未传入bucket_id";
    const E_NO_UPTOKEN = -210009;
    const M_NO_UPTOKEN = "没有上传token";
    const E_H5_UPLOAD_FAILED = -210010;
    const M_H5_UPLOAD_FAILED = "H5上传图片错误";
    const E_SUBMITED = -210011;
    const M_SUBMITED = "该订单号已经提交过车辆问题";


    //第三方
    const E_NO_PARTNER = -210001;
    const M_NO_PARTNER = "没有该合作伙伴";
    const E_FREQUENT_OPERATION = -210001;
    const M_FREQUENT_OPERATION = "没有该合作伙伴";
    const E_SHORT_OF_INFO = -220001;
    const M_SHORT_OF_INFO = "缺少信息";

    //蚂蚁金服芝麻信用 1毛
    const E_NO_THIS_MEMBER_ON_ZHIMA = -220001;
    const M_NO_THIS_MEMBER_ON_ZHIMA = "无此用户信息";
    const E_NO_THIS_AUTH_MEMBER_ON_ZHIMA = -220002;
    const M_NO_THIS_AUTH_MEMBER_ON_ZHIMA = "无此认证用户信息";
    const E_NO_NAME_MEMBER_ON_ZHIMA = -220003;
    const M_NO_NAME_MEMBER_ON_ZHIMA = "用户无有效姓名";
    const E_NO_IDNUMBER_AUTH_MEMBER_ON_ZHIMA = -220004;
    const M_NO_IDNUMBER_AUTH_MEMBER_ON_ZHIMA = "用户无有效身份证信息";
    const E_ZHIMA_FAILED = -220005;
    const M_ZHIMA_FAILED = "蚂蚁金服芝麻信用查询失败";
    
    //芝麻信用 4毛
    const E_NO_THIS_PARAMS_OR_SINE_ON_ZHIMAXINYONG = -230001;
    const M_NO_THIS_PARAMS_OR_SINE_ON_ZHIMAXINYONG = "未获取到params或者sign";
    const E_NO_THIS_MEMBER_ON_ZHIMAXINYONG = -230002;
    const M_NO_THIS_MEMBER_ON_ZHIMAXINYONG = "无此用户信息";
    const E_NO_THIS_AUTH_MEMBER_ON_ZHIMAXINYONG = -230003;
    const M_NO_THIS_AUTH_MEMBER_ON_ZHIMAXINYONG = "无此认证用户信息";
    const E_NO_NAME_OR_IDNUMBER_MEMBER_ON_ZHIMAXINYONG = -230004;
    const M_NO_NAME_OR_IDNUMBER_MEMBER_ON_ZHIMAXINYONG = "用户无有效姓名或者身份证信息";
    const E_NO_MOBILE_AUTH_MEMBER_ON_ZHIMAXINYONG = -230005;
    const M_NO_MOBILE_AUTH_MEMBER_ON_ZHIMAXINYONG = "用户无有效手机号";
    const E_ZHIMAXINYONG_FAILED = -230006;
    const M_ZHIMAXINYONG_FAILED = "芝麻信用查询失败";
    const E_GET_SCORE_FAILED = -230007;
    const M_GET_SCORE_FAILED = "芝麻信用无法给用户评分";

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
}