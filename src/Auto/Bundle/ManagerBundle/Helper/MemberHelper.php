<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 15/8/14
 * Time: 下午4:55
 */

namespace Auto\Bundle\ManagerBundle\Helper;
use Auto\Bundle\ManagerBundle\PushMessage\XingeApp;

class MemberHelper extends AbstractHelper{

    public function get_member_normalizer()
    {
        return function (\Auto\Bundle\ManagerBundle\Entity\Member $m ) {

            $progress_order = $this->orderHelper->get_progress_rental_order($m);
            $use_status = null;
            if(!empty($progress_order)){
                $use_status = $this->orderHelper->get_order_status($progress_order);
            }

            $member = [
                'userID'                        => $m->getToken(),
                'nickname'                      => $m->getNickname(),
                'name'                          => $m->getName(),
                'portrait'                      => $m->getPortrait()?$this->templating->render(
                    '{{ localname|photograph }}',
                    ['localname' => $m->getPortrait()]
                ):null,
                'sex'                           =>$m->getSex(),
                'authLicense'                   =>$this->get_license_auth_status($m),
                'mobile'                        =>$m->getMobile(),
                'age'                           =>$m->getAge(),
                'wallet'                        =>$m->getWallet(),
                'business'                      =>$m->getBusiness(),
                'job'                           =>$m->getJob(),
                'couponCount'                   =>$this->couponHelper->get_member_coupon_count($m),
                'illegalCount'                  =>$this->get_illegal_count($m),
                'messageCount'                  =>$this->get_unread_message_count($m),
                'progressOrderStatus'              =>$use_status,
                'progressOrderID'               =>empty($progress_order)?null:$progress_order->getId(),
                'noPayOrderID'                  =>$this->orderHelper->get_no_pay_rental_order($m),
                'orderFreeCancelCount'          =>$this->get_free_cancel_order_count($m)
            ];

            return $member;
        };
    }


    //2.4.0开始使用
    public function get_member_data_normalizer()
    {
        return function (\Auto\Bundle\ManagerBundle\Entity\Member $m ,$image = 0) {

            $progress_order = $this->orderHelper->get_progress_rental_order($m);
            $use_status = null;
            if(!empty($progress_order)){
                $use_status = $this->orderHelper->get_order_status($progress_order);
            }

            $depositAmount = $this->getMemberDepositInfo($m->getId());

            $member = [
                'userID'                        => $m->getToken(),
                'nickname'                      => $m->getNickname(),
                'name'                          => $m->getName(),
                'portrait'                      => $m->getPortrait()?$this->curlHelper->base_url().$this->templating->render(
                        '{{ localname|photograph }}',
                        ['localname' => $m->getPortrait()]
                    ):null,
                'sex'                           =>$m->getSex(),
                'authLicense'                   =>$this->get_license_auth_status($m),
                'mobile'                        =>$m->getMobile(),
                'age'                           =>$m->getAge(),
                'wallet'                        =>$m->getWallet(),
                'business'                      =>$m->getBusiness(),
                'job'                           =>$m->getJob(),
                'couponCount'                   =>$this->couponHelper->get_member_coupon_count($m),
                'illegalCount'                  =>$this->get_illegal_count($m),
                'messageCount'                  =>$this->get_unread_message_count($m),
                'progressOrderStatus'              =>$use_status,
                'progressOrderID'               =>empty($progress_order)?null:$progress_order->getId(),
                'noPayOrderID'                  =>$this->orderHelper->get_no_pay_rental_order($m),
                'orderFreeCancelCount'          =>$this->get_free_cancel_order_count($m),
                'hasPayDepositAmount'           =>$depositAmount
            ];

            return $member;
        };
    }



    public  function get_free_cancel_order_count(\Auto\Bundle\ManagerBundle\Entity\Member $member){

        $qb = $this->em->createQueryBuilder();

        $cancel_count =
            $qb
                ->select($qb->expr()->count('o'))
                ->from('AutoManagerBundle:rentalOrder', 'o')
                ->where($qb->expr()->eq('o.member', ':member'))
                ->andWhere($qb->expr()->lte('o.createTime', ':maxTime'))
                ->andWhere($qb->expr()->gte('o.createTime', ':minTime'))
                ->andWhere($qb->expr()->isNotNull('o.cancelTime'))
                ->setParameter('maxTime', (new \DateTime())->modify('+1 days')->format('Y-m-d'))
                ->setParameter('minTime', (new \DateTime())->format('Y-m-d'))
                ->setParameter('member', $member)
                ->getQuery()
                ->getSingleScalarResult()
        ;
        return $cancel_count;
    }

    public function getStatus(\Auto\Bundle\ManagerBundle\Entity\AuthMember $auth=null)
    {
        if(empty($auth)){

            $status = \Auto\Bundle\ManagerBundle\Entity\AuthMember::NO_UPDATE_AUTH;

        }else{

            $status = $auth->getStatusNew();
        }

        return $status;


    }

    public function get_unread_message_count($member){

        $qb = $this->em->createQueryBuilder();

        $message_count =
            $qb
                ->select($qb->expr()->count('m'))
                ->from('AutoManagerBundle:Message', 'm')
                ->where($qb->expr()->eq('m.member', ':member'))
                ->andWhere($qb->expr()->eq('m.status',':status'))
                ->setParameter('member', $member)
                ->setParameter('status', \Auto\Bundle\ManagerBundle\Entity\Message::MESSAGE_UNREAD)
                ->getQuery()
                ->getSingleScalarResult()
        ;
        return $message_count;
    }

    public function get_illegal_count($member){

        $qb = $this->em->createQueryBuilder();

        $illegal_count =
            $qb
                ->select($qb->expr()->count('i'))
                ->from('AutoManagerBundle:IllegalRecord', 'i')
                ->join('i.order','o')
                ->where($qb->expr()->eq('o.member', ':member'))
                ->andWhere($qb->expr()->isNull('i.handleTime'))
                ->setParameter('member', $member)
                ->getQuery()
                ->getSingleScalarResult()
        ;
        return $illegal_count;

    }

    /**
     * 获取某用户未处理的违章
     * @param $member
     * @return array
     */
    public function get_undo_illegal_record($member){
        $qb = $this->em->createQueryBuilder();
        $illegalRecord =
            $qb
                ->select('i')
                ->from('AutoManagerBundle:IllegalRecord', 'i')
                ->join('i.order','o')
                ->where($qb->expr()->eq('o.member', ':member'))
                ->andWhere($qb->expr()->isNull('i.handleTime'))
                ->andWhere($qb->expr()->isNull('i.agentTime'))
                ->setParameter('member', $member)
                ->getQuery()
                ->getResult()
        ;
        return $illegalRecord;

    }

    /**
     * 获取某用户未付款订单
     * @param $member
     * @return array
     */
    public function get_unpay_rental_order($member){
        $qb = $this->em->createQueryBuilder();
        $rentalOrder =
            $qb
                ->select('i')
                ->from('AutoManagerBundle:RentalOrder', 'i')
                ->where($qb->expr()->eq('i.member', ':member'))
                ->andWhere( $qb->expr()->isNull('i.payTime') )
                ->andWhere( $qb->expr()->isNotNull('i.useTime') )
                ->setParameter('member', $member)
                ->getQuery()
                ->getResult()
        ;
        return $rentalOrder;

    }

    /**
     * 获取某用户未缴费记录
     * @param $member
     * @return array
     */
    public function get_unpay_payment_order($member){
        $qb = $this->em->createQueryBuilder();
        $paymentOrder =
            $qb
                ->select('i')
                ->from('AutoManagerBundle:PaymentOrder', 'i')
                ->where($qb->expr()->eq('i.member', ':member'))
                ->andWhere( $qb->expr()->isNull('i.payTime') )
                ->setParameter('member', $member)
                ->getQuery()
                ->getResult()
        ;
        return $paymentOrder;

    }




    public function get_message_count($member){

        $qb = $this->em->createQueryBuilder();

        $message_count =
            $qb
                ->select($qb->expr()->count('o'))
                ->from('AutoManagerBundle:Message', 'm')
                ->where($qb->expr()->eq('m.member', ':member'))
                ->andWhere($qb->expr()->eq('m.read',':read'))
                ->setParameter('member', $member)
                ->setParameter('status', \Auto\Bundle\ManagerBundle\Entity\Message::MESSAGE_UNREAD)
                ->getQuery()
                ->getSingleScalarResult()
        ;
        return $message_count;

    }

    public function get_auth_error_message($error,$tel=1){

        if($error ==0)
            $str = "您所提交的身份认证已通过审核，您现在可以立即开始租赁车辆了。";
        if($error ==1)
            $str = "您的认证未通过，需拍照时保持证件照片图像内容清晰，请重新提交审核。";

        if($error ==2)
            $str = "您的认证未通过，需将驾驶证与驾驶证副页放置在一张照片中且图像内容清晰，请重新提交审核。";
        if($error ==3)
            $str = "您的认证未通过，需保证驾驶证领取时间满1年且驾龄已过实习期，请重新提交审核。";
        if($error ==4)
            $str = "您的认证未通过，需换领有效非过期的驾驶证，请重新提交审核。";
        if($error ==5)
            $str = "您的认证未通过，需保证所上传的驾驶证和驾驶证副页信息统一，请重新提交审核。";
        if($error ==6)
            $str = "您的认证未通过，需上传真实有效的驾驶证证件，请重新提交审核。";
        if($error ==7)
            $str = "您的认证未通过，认证期间工作人员无法与您取得联系，请重新提交审核。";
        if($error ==8)
            $str = "您的认证未通过，经工作人员核实驾驶证非用户本人持有，请重新提交审核。";
        if($error ==9)
            $str = "您的认证未通过，需提供给工作人员准确的驾驶证信息，请重新提交审核。";
        if ($tel == 1)
            $str .= "如有疑问，请联系客服400-111-8220";

        return $str;
    }

    public function get_auth_error_message_new($status,$tel=1){

        if($status == \Auto\Bundle\ManagerBundle\Entity\AuthMember::AUTH_SUCCESS)
            $str = "您所提交的身份认证已通过审核，您现在可以立即开始租赁车辆了。";
        if($status == \Auto\Bundle\ManagerBundle\Entity\AuthMember::AUTH_FAILED)
            $str = "您的实名认证未通过，请登录客户端查看原因。";
        if ($tel == 1)
            $str .= "如有疑问，请联系客服400-111-8220";

        return $str;
    }


    public function get_auth_error($status){

        $error_arr = [

            '0'=>'认证成功',

            '1'=>'证件照片内容不清晰',

            '2'=>'缺少驾驶证或驾驶证副页',

            '3'=>'驾驶证领取时间未满1年',

            '4'=>'驾驶证已过期',

            '5'=>'驾驶证与驾驶证副页信息不符',

            '6'=>'证件信息与交管系统信息不符',

            '7'=>'电话无人接听',

            '8'=>'不是本人',

            '9'=>'无法提供身份证信息'
        ];



        if(!empty($status)){

            return $error_arr[$status];

        }





    }

    /**
     * 驾驶证照片审核失败原因
     */
    public function get_license_image_auth_error($status){
        $error_arr = [

            '0'=>'审核通过',

            '1'=>'照片不清晰',

            '2'=>'缺少驾驶证或副页',

            '3'=>'驾驶证与副页信息不符',

            '4'=>'证件过期',

            '5'=>'与其它证件信息不符',

            '6'=>'非驾驶证'
        ];



        if(!empty($status)){

            return $error_arr[$status];

        }else{

            return '';

        }


    }
    /**
     * 身份证照片审核失败原因
     */
    public function get_id_image_auth_error($status){
        $error_arr = [


            '0'=>'审核通过',

            '1'=>'证件信息被遮挡',

            '2'=>'面部被遮挡',

            '3'=>'证件内容不清晰',

            '4'=>'非本人证件',

            '5'=>'非手持身份证'
        ];


        if(!empty($status)){

            return $error_arr[$status];

        }else{

            return '';

        }



    }
    /**
     * 手持身份证照片审核失败原因
     */
    public function get_id_hand_image_auth_error($status){
        $error_arr = [

            '0'=>'审核通过',

            '1'=>'证件信息被遮挡',

            '2'=>'面部被遮挡',

            '3'=>'证件内容不清晰',

            '4'=>'非本人证件',
            '5'=>'非手持身份证'
        ];


        if(!empty($status)){

            return $error_arr[$status];

        }else{
            return '';
        }



    }

    /**
     * 电话回访失败原因
     */
    public function get_mobile_call_error($status,$phone){

        $error_arr = [

            '0'=>'审核通过',

            '1'=>'照片不清晰',

            '2'=>'与其它证件信息不符',

            '3'=>'非身份证'
            
        ];


        if(!empty($status)){

            return $error_arr[$status];

        }else{

            return '';

        }



    }

    /**
     * 第三方认证失败原因
     */
    public function get_validate_error($status){

        $error_arr = [

            '0'=>'认证成功',

            '1'=>'认证信息未通过社会征信系统审核，请确定证件的合法性',
        ];

        if(!empty($status)){

            return $error_arr[$status];

        }else{
            return '';
        }
    }

    /**
     * 获取民族数组
     * @return array
     */
    public function get_member_nation(){
        $arrNation = [
            '汉族'=>'汉族',
            '满族'=>'满族',
            '回族'=>'回族',
            '壮族'=>'壮族',
            '苗族'=>'苗族',
            '维吾尔族'=>'维吾尔族',
            '土家族'=>'土家族',
            '蒙古族'=>'蒙古族',
            '藏族'=>'藏族',
            '布依族'=>'布依族',
            '瑶族'=>'瑶族',
            '朝鲜族'=>'朝鲜族',
            '白族'=>'白族',
            '哈尼族'=>'哈尼族',
            '哈萨克族'=>'哈萨克族',
            '黎族'=>'黎族',
            '傣族'=>'傣族',
            '彝族'=>'彝族',
            '侗族'=>'侗族',
            '东乡族'=>'东乡族',
            '高山族'=>'高山族',
            '水族'=>'水族',
            '佤族'=>'佤族',
            '纳西族'=>'纳西族',
            '土族'=>'土族',
            '羌族'=>'羌族',
            '锡伯族'=>'锡伯族',
            '阿昌族'=>'阿昌族',
            '普米族'=>'普米族',
            '塔吉克族'=>'塔吉克族',
            '布朗族'=>'布朗族',
            '鄂温克族'=>'鄂温克族',
            '撒拉族'=>'撒拉族',
            '毛南族'=>'毛南族',
            '怒族'=>'怒族',
            '京族'=>'京族',
            '基诺族'=>'基诺族',
            '德昂族'=>'德昂族',
            '保安族'=>'保安族',
            '俄罗斯族'=>'俄罗斯族',
            '乌兹别克族'=>'乌兹别克族',
            '门巴族'=>'门巴族',
            '鄂伦春族'=>'鄂伦春族',
            '独龙族'=>'独龙族',
            '塔塔尔族'=>'塔塔尔族',
            '赫哲族'=>'赫哲族',
            '珞巴族'=>'珞巴族',
            '裕固族'=>'裕固族',
            '景颇族'=>'景颇族',
            '达斡尔族'=>'达斡尔族',
            '柯尔克孜族'=>'柯尔克孜族',
            '仡佬族'=>'仡佬族',
            '拉祜族'=>'拉祜族',
            '傈僳族'=>'傈僳族',
            '畲族'=>'畲族',
            '仫佬族'=>'仫佬族',
        ];
        return $arrNation;
    }


    public function get_license_auth_status(\Auto\Bundle\ManagerBundle\Entity\Member $member=null){

        $auth = $this->em->getRepository('AutoManagerBundle:AuthMember')->findOneBy(['member'=>$member]);
        $status = empty($auth) ? \Auto\Bundle\ManagerBundle\Entity\AuthMember::NO_UPDATE_AUTH
                               : $auth->getStatusNew();
        /**
         * 2.3.0
         */
        $mobileCallError = '';
        $validateError = '';
        $auth_license_message['licenseAuthError'] = '';
        $auth_license_message['idImageAuthError'] = '';
        $auth_license_message['idHandImageAuthError'] = '';
        $validateDetailError = '';

        if($status == \Auto\Bundle\ManagerBundle\Entity\AuthMember::NO_UPDATE_AUTH){

            $message = "点击图片上传";
            return [
                'status'                    =>$status,
                'message'                   =>$message,
                'licenseImageMessage'=>"拍照时请横拿手机，并保证正副页在一张照片里。\n我们将会在24小时内审核您的信息",
                'licenseImage'=>''
            ];
        }else{
            if($status==\Auto\Bundle\ManagerBundle\Entity\AuthMember::AUTH_SUCCESS){

                $message = "已认证";
                $license_message = "恭喜，您的驾驶证信息审核通过";

            }
            if($status==\Auto\Bundle\ManagerBundle\Entity\AuthMember::AUTH_FAILED){

                $message = "认证失败";
                $license_message = $this->get_auth_error($auth->getLicenseAuthError());
                /**
                 * 2.3.0添加部分开始
                 */
                $phone = $auth->getMember()->getMobile();
                $auth_phone = substr_replace($phone,'****',3,4);


                if(!empty($auth->getLicenseImageAuthError())){

                    $auth_license_message['licenseAuthError'] = $this->get_license_image_auth_error($auth->getLicenseImageAuthError());


                }elseif($auth->getLicenseImageAuthError()===0){

                    $auth_license_message['licenseAuthError'] = '';

                }else{

                    $auth_license_message['licenseAuthError'] = $this->get_auth_error($auth->getLicenseAuthError());

                }


                $auth_license_message['idImageAuthError'] = $auth->getIdImageAuthError()===0?'':$this->get_id_image_auth_error($auth->getIdImageAuthError());
                $auth_license_message['idHandImageAuthError'] = $auth->getIdHandImageAuthError()===0?'':$this->get_id_hand_image_auth_error($auth->getIdHandImageAuthError());
                $mobileCallError = $auth->getMobileCallError()===0?'':$this->get_mobile_call_error($auth->getMobileCallError(),$auth_phone);
                $validateError = $auth->getValidateError()===0?'':$this->get_validate_error($auth->getValidateError());

                if($auth->getLicenseImageAuthError()>0 || $auth->getIdImageAuthError()>0 || $auth->getIdHandImageAuthError()>0){
                    $mobileCallError = '';
                    $validateError   = '';
                }

                //照片不全,针对老用户信息补充不完整
                if(empty($auth->getIdImage())||empty($auth->getIdHandImage())){

                    $mobileCallError = "信息缺少";

                }
                /**
                 * 2.3.0添加部分结束
                 */
                

                /**
                 * @身份证信息对比 | 身份证不良信息
                 * @驾驶证是否真实 | 手机号是否本人持有 错误信息
                 * @condition  在三张图片验证ALL OK时，输出错误信息
                 */
                if ( $auth->getLicenseImageAuthError()===0 &&
                     $auth->getIdImageAuthError()===0 &&
                     $auth->getIdHandImageAuthError()===0 ) {
                    
                    // 获取验证信息数据
                    $errorDetail = $auth->getValidateNewResult();

                    if (!empty($errorDetail)) {

                        $errorList = json_decode($errorDetail);
                        // ksort((array)$errorList);

                        // 输出首先取到的错误信息
                        foreach ($errorList as $key => $errorInfo) {
                            
                            if ($errorInfo->code != 200) {

                                switch ($key) {
                                    case 1:
                                        $validateDetailError = '手机号非本人持有';
                                        break;  
                                    case 2:
                                        $validateDetailError = '身份信息存在不良记录';
                                        break;
                                 /*   case 3:
                                        $validateDetailError = '驾驶证信息有误';*/
                                        break;
                                    case 4:
                                        $validateDetailError = '身份证信息有误';
                                        break;                                    
                                    default:
                                        break;
                                }
                            }
                        }

                    }
                }

            }
            if($status==\Auto\Bundle\ManagerBundle\Entity\AuthMember::AUTH_LICENSE_EXPIRE){
                $message = "已过期";
                $license_message = "证件已过有效期,请更换照片后重新上传";
                $auth_license_message['licenseAuthError']="驾驶证已过期";
            }
            if($status==\Auto\Bundle\ManagerBundle\Entity\AuthMember::UPDATED_NO_AUTH){
                $message = "认证中";
                $license_message = "您上传的信息正在审核中，请耐心等待";
            }
        }

        /**
         * 2.3.0证件改版验证图片基础url开始
         */
        if(!empty($auth->getLicenseImage())){

            $auth_license_image_host = $this->curlHelper->base_url();
        }else{


            $auth_license_image_host = '';

        }

        if(!empty($auth->getIdImage())){

            $auth_id_image_host = $this->curlHelper->base_url();

        }else{

            $auth_id_image_host = '';
        }

        if(!empty($auth->getIdHandImage())){

            $auth_id_hand_image_host = $this->curlHelper->base_url();

        }else{

            $auth_id_hand_image_host = '';
        }
        /**
         * 2.3.0证件改版验证图片基础url结束
         */


        return [
            'status'                    =>$status,
            'message'                   =>$message,
            'validateDetailError'       =>$validateDetailError,
            'licenseImageMessage'=>$license_message,
            'licenseImage'=>$this->templating->render(
                '{{ localname|photograph }}',
                ['localname' => $auth->getLicenseImage()]
            ),
            /**
             * 2.3.0开始
             */
            'mobileCallError'=>$mobileCallError,
            'validateError'=>$validateError,

            'authImageMessage'=>$auth_license_message,
            'authImage'=>array(

                'licenseImage'=>$auth_license_image_host.$this->templating->render(
                '{{ localname|photograph }}',
                ['localname' => $auth->getLicenseImage()]
                ),

                'idImage'=> $auth_id_image_host.$this->templating->render(
                    '{{ localname|photograph }}',
                    ['localname' => $auth->getIdImage()]
                ),

                'idHandImage'=>$auth_id_hand_image_host.$this->templating->render(
                    '{{ localname|photograph }}',
                    ['localname' => $auth->getIdHandImage()]
                ),

            ),
            /**
             * 2.3.0结束
             */

            'licenseEndDate'=>$auth->getLicenseEndDate()?$auth->getLicenseEndDate()->format('Y-m-d'):'',
            'licenseStartDate'=>$auth->getLicenseStartDate()?$auth->getLicenseStartDate()->format('Y-m-d'):'',
            'documentNumber'=>$auth->getDocumentNumber(),
            'IDNumber'=>$auth->getIDNumber(),
        ];
    }

    public function changeLoginMemberToken($member,$token,$platform,$kind){

        $qb = $this->em->createQueryBuilder();

        $man = $this->em;

        $device =
            $qb
                ->select('d')
                ->from('AutoManagerBundle:MobileDevice', 'd')
                ->where($qb->expr()->eq('d.member', ':member'))
                ->andWhere($qb->expr()->eq('d.kind', ':kind'))
                ->setParameter('member', $member)
                ->setParameter('kind', \Auto\Bundle\ManagerBundle\Entity\MobileDevice::MOBILE_DEVICE_KIND_LOGIN)
                ->orderBy('d.createTime', 'DESC')
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult()
        ;

        if(empty($device)){

            $mobileDevice = new \Auto\Bundle\ManagerBundle\Entity\MobileDevice();
            $mobileDevice->setDeviceToken($token);
            $mobileDevice->setMember($member);
            $mobileDevice->setPlatform($platform);
            $mobileDevice->setKind(\Auto\Bundle\ManagerBundle\Entity\MobileDevice::MOBILE_DEVICE_KIND_LOGIN);
            $man->persist($mobileDevice);
            $man->flush();
            //不用判断登录
            return true;
        }

        if($device->getDeviceToken() != $token){

            $qb = $this->em->createQueryBuilder();
            $mobile_device =
                $qb
                    ->select('d')
                    ->from('AutoManagerBundle:MobileDevice', 'd')
                    ->where($qb->expr()->eq('d.member', ':member'))
                    ->andWhere($qb->expr()->eq('d.kind', ':kind'))
                    ->andWhere($qb->expr()->eq('d.deviceToken', ':token'))
                    ->setParameter('member', $member)
                    ->setParameter('token', $token)
                    ->setParameter('kind', \Auto\Bundle\ManagerBundle\Entity\MobileDevice::MOBILE_DEVICE_KIND_LOGIN)
                    ->orderBy('d.createTime', 'DESC')
                    ->setMaxResults(1)
                    ->getQuery()
                    ->getOneOrNullResult()
            ;

            if(empty($mobile_device)){

                $mobileDevice = new \Auto\Bundle\ManagerBundle\Entity\MobileDevice();
                $mobileDevice->setDeviceToken($token);
                $mobileDevice->setMember($member);
                $mobileDevice->setPlatform($platform);
                $mobileDevice->setKind(\Auto\Bundle\ManagerBundle\Entity\MobileDevice::MOBILE_DEVICE_KIND_LOGIN);
                $man->persist($mobileDevice);
                $man->flush();
            }


            $qb = $this->em->createQueryBuilder();

            $devices =
                $qb
                    ->select('m')
                    ->from('AutoManagerBundle:MobileDevice', 'm')
                    ->where($qb->expr()->eq('m.member', ':member'))
                    ->andWhere($qb->expr()->neq('m.deviceToken', ':token'))
                    ->andWhere($qb->expr()->eq('m.kind',':kind'))
                    ->setParameter('member', $member)
                    ->setParameter('token', $token)
                    ->setParameter('kind', \Auto\Bundle\ManagerBundle\Entity\MobileDevice::MOBILE_DEVICE_KIND_LOGIN)
                    ->getQuery()
                    ->getResult()
            ;

            array_map(function($d) use($man,$device){
                //给设备发推送下线

                $message = "您的账号于".(new \DateTime())->format('Y-m-d H:i')."在另一个手机端登录。如非本人操作，则密码有可能泄露，请及时修改密码。";
                $subject = 4;

                if($d->getPlatform() ==\Auto\Bundle\ManagerBundle\Entity\MobileDevice::MOBILE_PLATFORM_IOS){

                    $this->pushHelper->pushTokenIos($message,$d->getDeviceToken(),['subject'=>$subject]);

                }else{

                    $this->pushHelper->pushTokenAndroid($message,$d->getDeviceToken(),['subject'=>$subject]);

                }

                $d->setMember(null);
                $man->persist($d);
                $man->flush();

            },$devices);

            return false;

        }else{
            return true;
        }

    }

    private function getMemberDepositInfo($memberId)
    {

        $qb = $this->em->createQueryBuilder();

        $deposit = $qb
            ->select('d')
            ->from('AutoManagerBundle:Deposit', 'd')
            ->andwhere($qb->expr()->eq('d.member', ':member'))
            ->setParameter('member', $memberId)
            ->getQuery()
            ->getOneOrNullResult();

        $amount = isset($deposit) && $deposit ? $deposit->getTotal(): 0;
        return $amount;
    }


    public function setOrderHelper($orderHelper)
    {
        $this->orderHelper = $orderHelper;
    }
    public function setCouponHelper($couponHelper)
    {
        $this->couponHelper = $couponHelper;
    }
    public function setPushHelper($pushHelper)
    {
        $this->pushHelper = $pushHelper;
    }
    public function setCurlHelper($curlHelper)
    {
        $this->curlHelper = $curlHelper;
    }


}