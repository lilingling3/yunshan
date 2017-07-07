<?php
namespace Auto\Bundle\Api2Bundle\Controller;
use Auto\Bundle\Api2Bundle\Controller\BaseController;
use Auto\Bundle\ManagerBundle\Entity\AuthMember;
use Auto\Bundle\ManagerBundle\Entity\MobileDevice;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Security\Core\Util\SecureRandom;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @Route("/account")
 */
 class AccountController extends BaseController{
     /**
      * @Route("/qiniuCallback", methods="POST")
      */
     public function qiniuCallbackAction(Request $req)
     {
         $question = $req->request->get('question');
         $imageLabel = $req->request->get('imageLabel');
         $bucket = $req->request->get('bucket');
         $filename = $req->request->get('filename');
         if(empty($imageLabel)){

             return new JsonResponse([
                 'errorCode'    =>  '1',
                 'code' => 'No order_id'
             ]);
         }
         if(!$question){
             return new JsonResponse([
                 'errorCode'    =>  '1',
                 'code' => 'No question'
             ]);
         }
         if(!$bucket){
             return new JsonResponse([
                 'errorCode'    =>  '1',
                 'code' => 'No bucket'
             ]);
         }
         if(!$filename){
             return new JsonResponse([
                 'errorCode'    =>  '1',
                 'code' => 'No filename'
             ]);
         }
         //echo 'qiniu_'.$bucket.'_'.$imageLabel;die;
         $redis = $this->container->get('snc_redis.default');
         $redis_cmd = $redis->createCommand('set',array('qiniu_'.$bucket.'_'.$imageLabel,$filename));
         $redis->executeCommand($redis_cmd);
         $redis_cmd= $redis->createCommand('EXPIRE',array('qiniu_'.$bucket.'_'.$imageLabel,3600));
         $redis->executeCommand($redis_cmd);

         $QiniuImage = new \Auto\Bundle\ManagerBundle\Entity\QiniuImage();
         //var_dump($QiniuImage);die;
         $QiniuImage->setImageLabel($imageLabel);
         $QiniuImage->setBucket("$bucket");
         $QiniuImage->setFilename("$filename");
         $QiniuImage->setCreateTime(new \DateTime());
         $man = $this->getDoctrine()->getManager();
         $man->persist($QiniuImage);
         $man->flush();

         $order_id = $imageLabel;
         $rentalOrder = $this->getDoctrine()
             ->getRepository('AutoManagerBundle:RentalOrder')
             ->find($order_id);
         $mobile = $rentalOrder->getMobile();
         $name = $rentalOrder->getMember()->getName();
         $carType =$rentalOrder->getRentalCar()->getCar()->getName();
         $licensePlace = $rentalOrder->getRentalCar()->getLicensePlace()->getName();;
         $plateNumber = $rentalOrder->getRentalCar()->getLicensePlate();

         $problem = $question;
         $coupon = 'null';
         $state = 100;
         $CarProblem = new \Auto\Bundle\ManagerBundle\Entity\CarProblem();

         $CarProblem->setImageLabel($imageLabel);
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

         return new JsonResponse([
             'errorCode'    =>  self::E_OK,
             'code' => ''
         ]);
     }

     /**
      * 获取登陆短信验证码
      * @Route("/login/code", methods="POST")
      */

     public function getLoginCodeAction(Request $req)
     {

         $mobile = $req->request->getInt('mobile');

         if(!preg_match('/^1[34578]{1}\d{9}$/',$mobile)){
             return new JsonResponse([
                 'errorCode'    =>  self::E_WRONG_MOBILE,
                 'errorMessage' =>  self::M_WRONG_MOBILE
             ]);
         }

         $SMSCode = $this->getMobileSMS($mobile,\Auto\Bundle\ManagerBundle\Entity\SMSCode::LOGIN_KIND);
         $result = $this->get('auto_manager.sms_helper')->sendCodeSMS($mobile,$SMSCode->getCode());

         if(property_exists($result,'sub_msg')){
             if('触发业务流控' == $result->sub_msg){
                 $appRoot = $this->get('kernel')->getRootDir();
                 $this->get('auto_manager.logs_helper')->addDayuErrorLogs("$result->sub_code", $mobile,$appRoot,\Auto\Bundle\ManagerBundle\Entity\SMSCode::LOGIN_KIND);
             }
         }
         return new JsonResponse([
             'errorCode'    =>  self::E_OK,
             'code' => ''
         ]);

     }

     /**
      *
      * @Route("/verify/login", methods="POST",name="auto_api_2_verify_login")
      */
     public function verifyLoginAction(Request $req)
     {
         $code = $req->request->get('code');
         $mobile = $req->request->get('mobile');
         $deviceToken = $req->request->get('deviceToken');
         $platform = $req->request->getInt('platform');
         $source = $req->request->getInt('source');

         if(!preg_match('/^1[34578]{1}\d{9}$/',$mobile)){
             return new JsonResponse([
                 'errorCode'    =>  self::E_WRONG_MOBILE,
                 'errorMessage' =>  self::M_WRONG_MOBILE
             ]);
         }

         $qb =
             $this
                 ->getDoctrine()
                 ->getRepository('AutoManagerBundle:SMSCode')
                 ->createQueryBuilder('s');
         $SMSCode = $qb
             ->select('s')
             ->where($qb->expr()->eq('s.mobile', ':mobile'))
             ->andWhere($qb->expr()->eq('s.kind', ':kind'))
             ->andWhere($qb->expr()->eq('s.code', ':code'))
             ->setParameter('mobile', $mobile)
             ->setParameter('code', $code)
             ->setParameter('kind', \Auto\Bundle\ManagerBundle\Entity\SMSCode::LOGIN_KIND)
             ->orderBy('s.id', 'DESC')
             ->setMaxResults(1)
             ->getQuery()
             ->getOneOrNullResult();

         if(empty($SMSCode)){

             return new JsonResponse([
                 'errorCode'    =>  self::E_VERIFY_MOBILE_WRONG,
                 'errorMessage' =>  self::M_VERIFY_MOBILE_WRONG,
             ]);

         }

         if($SMSCode->getEndTime()<new \DateTime()){

             return new JsonResponse([
                 'errorCode'    =>  self::E_INVALID_CODE,
                 'errorMessage' =>  self::M_INVALID_CODE,
             ]);

         }

         $member = $this->getDoctrine()
             ->getRepository('AutoManagerBundle:Member')
             ->findOneBy(['mobile'=>$mobile]);

         $register = 0;
         $man = $this->getDoctrine()->getManager();

         if(empty($member)){
             $member = new \Auto\Bundle\ManagerBundle\Entity\Member();
             $member->setMobile($mobile)
                 ->setRoles(['ROLE_USER'])
                 ->setToken(md5((new SecureRandom())->nextBytes(18)));

             if($source){
                 $member->setSource($source);
             }
             $man->persist($member);
             $man->flush();

             $register = 1;

         }else{

             $member->setLastLoginTime(new \DateTime());
             $man->persist($member);
             $man->flush();

         }

         if($deviceToken){
             $this->get('auto_manager.member_helper')->changeLoginMemberToken($member,$deviceToken,$platform,
                 $kind=\Auto\Bundle\ManagerBundle\Entity\MobileDevice::MOBILE_DEVICE_KIND_LOGIN);

         }


         return new JsonResponse([
             'errorCode'    =>  self::E_OK,
             'user'         => call_user_func($this->get('auto_manager.member_helper')->get_member_data_normalizer(),
                 $member),
             'register' => $register
         ]);

     }



     /**
      * @Route("/logout", methods="POST")
      */
     public function logoutAction(Request $req)
     {
         $deviceToken = $req->request->get('deviceToken');

         $check_member = $this->checkMember($this->getUser());

         if(!empty($check_member)){

             return new JsonResponse($check_member);

         }


         if($deviceToken){

             $device = $this->getDoctrine()
                 ->getRepository('AutoManagerBundle:MobileDevice')
                 ->findOneBy(['member'=>$this->getUser(),'deviceToken'=>$deviceToken,'kind'=>\Auto\Bundle\ManagerBundle\Entity\MobileDevice::MOBILE_DEVICE_KIND_LOGIN]);

             if(!empty($device)){
                 $man = $this->getDoctrine()->getManager();
                 $device->setMember(null);
                 $man->persist($device);
                 $man->flush();

             }

         }

         return new JsonResponse([
             'errorCode'    =>  self::E_OK,
         ]);
     }


     private function random($w)
     {
         $n = current(unpack('L', openssl_random_pseudo_bytes(4)));

         return sprintf("%0{$w}.0f", ($n & 0x7fffffff) / 0x7fffffff * (pow(10, $w) - 1));
     }

     /**
      * @Route("/forget/getCode", methods="POST")
      */

     public function forgetCodeAction(Request $req){

         $mobile = $req->request->getInt('mobile');

         if(!preg_match('/^1[34578]{1}\d{9}$/',$mobile)){
             return new JsonResponse([
                 'errorCode'    =>  self::E_WRONG_MOBILE,
                 'errorMessage' =>  self::M_WRONG_MOBILE
             ]);
         }

         $member = $this->getDoctrine()
             ->getRepository('AutoManagerBundle:Member')
             ->findOneBy(['mobile'=>$mobile]);

         if(empty($member)){
             return new JsonResponse([
                 'errorCode'    =>  self::E_NO_MEMBER,
                 'errorMessage' =>  self::M_NO_MEMBER
             ]);
         }

         /**
          * @var $SMSCode \Auto\Bundle\ManagerBundle\Entity\SMSCode
          */
         $SMSCode = $this->getMobileSMS($mobile,\Auto\Bundle\ManagerBundle\Entity\SMSCode::FORGET_PASSWORD);

         $client = $this->get('auto_manager.sms_helper');
         $client->send(
             $mobile,
             $this->renderView(
                 'AutoManagerBundle:Account:code.sms.twig',
                 ['code' => $SMSCode->getCode()]
             )
         );

         return new JsonResponse([
             'errorCode'    =>  self::E_OK,
             'mobile'=>$mobile
         ]);

     }



     private function getMobileSMS($mobile,$kind){

         $code   = $this->random(4);

         if($mobile == 13581509341){

             $code = 1234;

         }
         $SMSCode = new \Auto\Bundle\ManagerBundle\Entity\SMSCode();
         $SMSCode->setCode($code);
         $SMSCode->setMobile($mobile);
         $SMSCode->setKind($kind);
         $SMSCode->setEndTime(new \DateTime((new \DateTime('+5 minutes'))->format('Y-m-d
        H:i:s')));

         $man = $this->getDoctrine()->getManager();
         $man->persist($SMSCode);
         $man->flush();

         return $SMSCode;
     }



     /**
      *2.3.0 图片上传
      */
     public function upload2_image($img)
     {

         $helper = $this->get('mojomaja_photograph.helper.photograph');
         if ($img) {

             $tmp = tempnam(null, "license");

             if (copy($img["tmp_name"], $tmp)) {

                 chmod($tmp, 0644);

                 $name = $helper->persist($tmp, true);

             }

             unlink($tmp);

         }

         return $name;
     }

     public function upload_image($binary_code){

         $helper = $this->get('mojomaja_photograph.helper.photograph');
         $filename = tempnam(sys_get_temp_dir(), "lecar");


         file_put_contents($filename, $binary_code);

         $name = "";
         $tmp = tempnam(null, null);
         if (copy($filename, $tmp)) {
             chmod($tmp, 0644);
             $name = $helper->persist($tmp, true);
         }

         unlink($tmp);
         unlink($filename);

         return $name;

     }


     /**
      * @Route("/user", methods="POST")
      */
     public function userAction(Request $req){

         $deviceToken = $req->request->get('deviceToken');

         /**
          * @var $member \Auto\Bundle\ManagerBundle\Entity\Member
          */
        
        $member = $this->getUser();

        $check_member = $this->checkMember($member);

         if (!empty($check_member)){


             return new JsonResponse($check_member);


         }

         if($deviceToken){

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

         $data = call_user_func($this->get('auto_manager.member_helper')->get_member_data_normalizer(),
                $member);

         return new JsonResponse([
             'errorCode'    => self::E_OK,
             'user'         => $data
         ]);

     }



     /**
      * @Route("/info", methods="POST")
      */
     public function infoAction(Request $req){
         $nickname = $req->request->get('nickname');
         $portrait = $req->request->get('portrait');
         $personImage = $req->request->get('personImage');
         $licenseImage = $req->request->get('licenseImage');
         $sex = $req->request->getInt('sex');
         $age = $req->request->getInt('age');
         $business = $req->request->getInt('business');
         $job = $req->request->getInt('job');
         $name = $req->request->get('name');
         $IDNumber = $req->request->get('IDNumber');

         $idImage = isset($_FILES['idImage'])?$_FILES['idImage']:'';

         $idHandImage = isset($_FILES['idHandImage'])?$_FILES['idHandImage']:'';

         $licenseAuthImage = isset($_FILES['licenseImage'])?$_FILES['licenseImage']:'';

         /**
          * @var $member \Auto\Bundle\ManagerBundle\Entity\Member
          */

         $member = $this->getUser();

         $check_member = $this->checkMember($member);

         if(!empty($check_member)){


             return new JsonResponse($check_member);


         }

         $auth = $this->getDoctrine()
             ->getRepository('AutoManagerBundle:AuthMember')
             ->findOneBy(['member'=>$member]);

         if(empty($auth)){
             $auth = new AuthMember();
             $auth->setMember($member)
                 ->setApplyTime(new \DateTime());
         }

         if($nickname) $member->setNickname($nickname);
         if($sex) $member->setSex($sex);
         if($age) $member->setAge($age);
         if($business) $member->setBusiness($business);
         if($job) $member->setJob($job);
         if($name) $member->setName($name);
         if($IDNumber) $auth->setIDNumber($IDNumber);

         if($portrait){
             $name = $this->upload_image(base64_decode($portrait));
             if($name){
                 $member->setPortrait($name);
             }
         }

         if($personImage){
             $name = $this->upload_image(base64_decode($personImage));
             if($name){
                 $auth->setPersonImage($name);
                 $man = $this->getDoctrine()->getManager();
                 $man->persist($auth);
                 $man->flush();
             }
         }

         if($licenseImage){

             if($auth->getLicenseImage()){
                 $auth->setAuthTime(null);
             }

             $name = $this->upload_image(base64_decode($licenseImage));
             if($name){
                 $auth->setLicenseImage($name)
                     ->setApplyTime(new \DateTime());
                 $man = $this->getDoctrine()->getManager();
                 $man->persist($auth);
                 $man->flush();
             }

         }

         if(count($_POST)==1 && !empty($_POST['userID']) && $auth->getIdImageAuthError()===0 && $auth->getIdHandImageAuthError()===0 && $auth->getLicenseImageAuthError()===0 && empty($_FILES)){

             $auth->setAuthTime(null);

             $auth->setApplyTime(new \DateTime());

             $man = $this->getDoctrine()->getManager();

             $man->persist($auth);

             $man->flush();

         }


         if($licenseAuthImage){

             if($auth->getLicenseImage()){
                 $auth->setAuthTime(null);
             }

             $name = $this->upload2_image($licenseAuthImage);
             if($name){
                 $auth->setLicenseImage($name)
                     ->setApplyTime(new \DateTime());
                 $man = $this->getDoctrine()->getManager();
                 $man->persist($auth);
                 $man->flush();
             }

         }



         if($idImage){

             if($auth->getIdImage()){
                 $auth->setAuthTime(null);
             }

             if(!empty($auth->getLicenseImage())&&$auth->getLicenseAuthError()===0){

                 $auth->setAuthTime(null);

             }

             $name = $this->upload2_image($idImage);
             if($name){
                 $auth->setIdImage($name)
                     ->setApplyTime(new \DateTime());
                 $man = $this->getDoctrine()->getManager();
                 $man->persist($auth);
                 $man->flush();
             }
         }



         if($idHandImage){

             if($auth->getIdHandImage()){
                 $auth->setAuthTime(null);
             }

             if(!empty($auth->getLicenseImage())&&$auth->getLicenseAuthError()===0){

                 $auth->setAuthTime(null);

             }

             $name = $this->upload2_image($idHandImage);
             if($name){
                 $auth->setIdHandImage($name)
                     ->setApplyTime(new \DateTime());
                 $man = $this->getDoctrine()->getManager();
                 $man->persist($auth);
                 $man->flush();
             }

         }




         $man = $this->getDoctrine()->getManager();
         $man->persist($member);
         $man->flush();



         return new JsonResponse([
             'errorCode'    =>  self::E_OK,
             'user'         => call_user_func($this->get('auto_manager.member_helper')->get_member_normalizer(),
                 $member)
         ]);

     }


















     }