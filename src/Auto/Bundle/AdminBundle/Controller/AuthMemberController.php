<?php

namespace Auto\Bundle\AdminBundle\Controller;

use Auto\Bundle\ManagerBundle\Entity\AuthMember;
use Auto\Bundle\ManagerBundle\Form\AuthMemberNewType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Auto\Bundle\ManagerBundle\Entity\Area;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\Tools\Pagination\Paginator;


/**
 * @Route("/authMember")
 */
class AuthMemberController extends Controller
{
    const PER_PAGE = 20;

    /**
     * @Route("/getMessage", methods="GET")
     */
    public function getMessageAction()
    {

        $message = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:AuthMember')
            ->findOneBy(['authTime'=>null]);

        $status = empty($message)?false:true;
        return new JsonResponse(['status'=>$status]);

    }

    /**
     * @Route("/auth/list/{page}", methods="GET", name="auto_admin_authmember_auth_list",
     * requirements={"page"="\d+"},
     * defaults={"page"=1})
     * @Template()
     */
    public function authListAction(Request $req,$page = 1)
    {
        $status = $req->query->getInt('status');  // 2审核中 3 审核失败 4 审核成功
        $mobile = $req->query->get('mobile');
        $name = $req->query->get('name');

        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:AuthMember')
                ->createQueryBuilder('m')
                ->select('m')
                ->join('m.member','u')
        ;

        if($mobile){
            $qb
                ->andWhere($qb->expr()->eq('u.mobile', ':mobile'))
                ->setParameter('mobile', $mobile);

        }elseif($name){
            $qb
                ->andWhere($qb->expr()->eq('u.name', ':name'))
                ->setParameter('name', $name);
        }

        if($status == \Auto\Bundle\ManagerBundle\Entity\AuthMember::UPDATED_NO_AUTH){
            $qb->andWhere($qb->expr()->isNull('m.authTime'));
        }

        if($status == \Auto\Bundle\ManagerBundle\Entity\AuthMember::AUTH_FAILED){
            $qb
                ->orWhere($qb->expr()->neq('m.licenseImageAuthError', ':licenseImageAuthError'))
                ->setParameter('licenseImageAuthError', 0)

                ->orWhere($qb->expr()->neq('m.idImageAuthError', ':idImageAuthError'))
                ->setParameter('idImageAuthError', 0)

                ->orWhere($qb->expr()->neq('m.idHandImageAuthError', ':idHandImageAuthError'))
                ->setParameter('idHandImageAuthError', 0)

                ->orWhere($qb->expr()->neq('m.mobileCallError', ':mobileCallError'))
                ->setParameter('mobileCallError', 0)

                ->orWhere($qb->expr()->neq('m.validateError', ':validateError'))
                ->setParameter('validateError', 0)

                ->orWhere($qb->expr()->isNull('m.validateError'))

                ->orWhere($qb->expr()->lt('m.licenseEndDate', ':time'))
                ->setParameter('time', (new \DateTime()))

                ;

        }

        if($status == \Auto\Bundle\ManagerBundle\Entity\AuthMember::AUTH_SUCCESS){
            $qb


                ->andWhere($qb->expr()->isNotNull('m.authTime'))
                ->andWhere($qb->expr()->gt('m.licenseEndDate', ':time'))
                ->setParameter('time', (new \DateTime()))


                ->andWhere($qb->expr()->eq('m.licenseImageAuthError', ':licenseImageAuthError'))
                ->setParameter('licenseImageAuthError', 0)

                ->andWhere($qb->expr()->eq('m.idImageAuthError', ':idImageAuthError'))
                ->setParameter('idImageAuthError', 0)

                ->andWhere($qb->expr()->eq('m.idHandImageAuthError', ':idHandImageAuthError'))
                ->setParameter('idHandImageAuthError', 0)

                ->andWhere($qb->expr()->eq('m.mobileCallError', ':mobileCallError'))
                ->setParameter('mobileCallError', 0)

                ->andWhere($qb->expr()->eq('m.validateError', ':validateError'))
                ->setParameter('validateError', 0)

                ;
            ;
        }

        if($status == \Auto\Bundle\ManagerBundle\Entity\AuthMember::AUTH_LICENSE_EXPIRE){
            $qb
                ->andWhere($qb->expr()->isNotNull('m.authTime'))
                ->andWhere($qb->expr()->isNotNull('m.licenseEndDate'))

                ->andWhere($qb->expr()->lt('m.licenseEndDate', ':time'))
                ->setParameter('time', (new \DateTime()));
            ;
        }


        $auth_members =
            new Paginator(
                $qb
//                    ->andWhere($qb->expr()->isNotNull('m.idImage'))
//                    ->andWhere($qb->expr()->isNotNull('m.idHandImage'))
//                    ->andWhere($qb->expr()->isNotNull('m.licenseImage'))
                    ->orderBy('m.applyTime', 'DESC')
                    ->setMaxResults(self::PER_PAGE)
                    ->setFirstResult(self::PER_PAGE * ($page - 1))
            );
        $time=[];
        foreach($auth_members as $auth){
            if(!isset($time[$auth->getId()])){
                $time[$auth->getId()]=0;
            }
            if($auth->getAuthTime()){
                $start=$auth->getApplyTime()?$auth->getApplyTime():$auth->getCreateTime();
                $t=$auth->getAuthTime()->getTimestamp()-$start->getTimestamp();
            }else{
                $t=0;
            }


            $d = floor($t/3600/24);
            $h = floor(($t%(3600*24))/3600);  //%取余
            $m = floor(($t%(3600*24))%3600/60);
            $s = floor(($t%(3600*24))%60);

            $minutes=$d*24*60+$h*60+$m;
            $time[$auth->getId()]="$minutes 分 $s 秒";
        }

        $total = ceil(count($auth_members) / self::PER_PAGE);
;

        return ['auth_members'=>$auth_members,'time'=>$time,'page'=>$page,'total'=>$total,'status'=>$status];
    }



    /**
     * @Route("/auth/search", methods="POST", name="auto_admin_authmember_auth_search")
     * @Template()
     */
    public function authSearchAction(Request $req){
        return $this->redirect(
            $this->generateUrl(
                'auto_admin_authmember_auth_list',
                [
                    'status' => trim($req->request->getInt('status')),
                    'mobile' => trim($req->request->get('mobile')),
                    'name' => trim($req->request->get('name')),
                ]
            )
        );

    }





    /**
     * @Route("/auth/edit/{id}/{page}", methods="GET", name="auto_admin_authmember_auth_edit",defaults={"page"=1})
     * @Template()
     */
    public function authEditAction($id,$page,Request $req)
    {
        $type = $req->query->getInt('type');
        $auth = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:AuthMember')
            ->find($id);
        if($type == 1){//表示是审核
            if(!empty($auth->getAuthTime())){
                return $this->render(
                    "AutoAdminBundle:Default:message.html.twig",
                    ['message'=>'此用户已审核完毕']
                );
            }
            $value = $this->getUser()->getId();
            $key = 'admin-auth-member-'.$id."-is-edit";
            $redis = $this->container->get('snc_redis.default');
            $redis_cmd= $redis->createCommand('GET',array($key));
            $flag = $redis->executeCommand($redis_cmd);
            if($flag != null && $flag != $value){
                return $this->render(
                    "AutoAdminBundle:Default:message.html.twig",
                    ['message'=>'其他客服正在审核中，请选择其他用户']
                );
            }
            $redis_cmd= $redis->createCommand('SET',array($key,$value));
            $redis->executeCommand($redis_cmd);
            $redis_cmd= $redis->createCommand('EXPIRE',array($key,300));
            $redis->executeCommand($redis_cmd);
        }

        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->find($auth->getMember()->getId());

        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:BlackList')
                ->createQueryBuilder('a')
                ->select('a')
                ->join('a.authMember','b')
        ;
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
        $form = $this->createFormBuilder()
            ->add('AuthMember', new AuthMemberNewType(),['data'=>$auth])
            ->add('name', 'text', ['data' => $member->getName(),'required' => false])
            ->add('IdNumber', 'text', ['data' => $member->getIdNumber(),'required' => false])
            ->add('sex', 'choice',array(
                'data' => $member->getSex(),
                'choices'  => [
                    \Auto\Bundle\ManagerBundle\Entity\Member::MEMBER_SEX_UNKNOWN=>'未知',
                    \Auto\Bundle\ManagerBundle\Entity\Member::MEMBER_SEX_MALE=>'男',
                    \Auto\Bundle\ManagerBundle\Entity\Member::MEMBER_SEX_FEMALE=>'女'
                ]

            ))
            ->add('address', 'text', ['data' => $member->getAddress(),'required' => false])
            ->add('nation', 'choice', array(
                'data' => $member->getNation(),
                'choices'  => $this->get("auto_manager.member_helper")->get_member_nation()
            ))
            ->setAction($this->generateUrl('auto_admin_authmember_auth_update', ['id' => $auth->getId()]))
            ->getForm();


        return ['form'  => $form->createView(),'auth'=>$auth,"blacked"=>$blacked];

    }

    /**
     * @Route("/auth/show/{id}/{page}", methods="GET", name="auto_admin_authmember_auth_show",defaults={"page"=1})
     * @Template()
     */
    public function authShowAction($id,$page,Request $req)
    {
        $auth = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:AuthMember')
            ->find($id);

        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->find($auth->getMember()->getId());
        $today=new \DateTime();
        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:BlackList')
                ->createQueryBuilder('a')
                ->select('a')
                ->join('a.authMember','b')
        ;
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

       $verifys=$data = json_decode($auth->getValidateNewResult(),true);

        $authinfo = $this->get('auto_manager.member_helper')->get_license_auth_status($auth->getMember());

      // dump($auth);exit;
      //  dump($authinfo);exit;
        return ['auth'=>$auth,'member'=>$member,"today"=>$today,"blacked"=>$blacked,"verifys"=>$verifys,'authinfo'=>$authinfo];

    }


    /**
     * @Route("/auth/edit/{id}/{page}", methods="POST", name="auto_admin_authmember_auth_update",defaults={"page"=1},
     * requirements={"id"="\d+"})
     * @Template("AutoAdminBundle:AuthMember:authEdit.html.twig")
     */
    public function updateAction(Request $req, $id,$page)
    {
        $key = 'admin-auth-member-'.$id."-is-edit";
        $redis = $this->container->get('snc_redis.default');
        $redis_cmd= $redis->createCommand('DEL',array($key));
        $redis->executeCommand($redis_cmd);
        $auth = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:AuthMember')
            ->find($id);

        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->find($auth->getMember()->getId());

        $form = $this->createFormBuilder()
            ->add('AuthMember', new AuthMemberNewType(),['data'=>$auth])
            ->add('name', 'text', ['data' => $member->getName(),'required' => false])
            ->add('IdNumber', 'text', ['data' => $member->getIdNumber(),'required' => false])
            ->add('sex', 'choice',array(
                'data' => $member->getSex(),
                'choices'  => [
                    \Auto\Bundle\ManagerBundle\Entity\Member::MEMBER_SEX_UNKNOWN=>'未知',
                    \Auto\Bundle\ManagerBundle\Entity\Member::MEMBER_SEX_MALE=>'男',
                    \Auto\Bundle\ManagerBundle\Entity\Member::MEMBER_SEX_FEMALE=>'女'
                ]

            ))
            ->add('address', 'text', ['data' => $member->getAddress(),'required' => false])
            ->add('nation', 'choice', array(
                'data' => $member->getNation(),
                'choices'  => $this->get("auto_manager.member_helper")->get_member_nation()
            ))
            ->setAction($this->generateUrl('auto_admin_authmember_auth_update', ['id' => $auth->getId()]))
            ->getForm();

        $form->handleRequest($req);
        if ($form->isValid()) {

            $data = $form->getData();

            $auth = $data['AuthMember'];
            if($data['name'] != $member->getName()){
                $auth->getMember()->setName($data['name']);
            }
            $auth->getMember()->setIdNumber($data['IdNumber']);
            $auth->getMember()->setSex($data['sex']);
            $auth->getMember()->setAddress($data['address']);
            $auth->getMember()->setNation($data['nation']);
            $auth->setAuthTime(new \DateTime());
            $man = $this->getDoctrine()->getManager();
            $man->persist($auth);
            $man->flush();

            $auth_license = $this->get("auto_manager.member_helper")->get_license_auth_status($auth->getMember());


            if($auth_license['status']==\Auto\Bundle\ManagerBundle\Entity\AuthMember::AUTH_SUCCESS||$auth_license['status']==\Auto\Bundle\ManagerBundle\Entity\AuthMember::AUTH_FAILED){




                if($auth_license['status']==\Auto\Bundle\ManagerBundle\Entity\AuthMember::AUTH_SUCCESS){

                    $this->get("auto_manager.sms_helper")->authSuccessSMS(
                        $auth->getMember()->getMobile()
                    );

                }else{

                    $this->get("auto_manager.sms_helper")->authFailedSMS(
                        $auth->getMember()->getMobile()
                    );

                }






                $message = new \Auto\Bundle\ManagerBundle\Entity\Message();
                $message->setContent($this->get("auto_manager.member_helper")->get_auth_error_message_new($auth_license['status'],0))
                    ->setKind(1)
                    ->setMember($member)
                    ->setStatus(\Auto\Bundle\ManagerBundle\Entity\Message::MESSAGE_UNREAD)
                ;
                $man = $this->getDoctrine()->getManager();
                $man->persist($message);
                $man->flush();
            }



            return $this->redirect($this->generateUrl('auto_admin_authmember_auth_list',['page'=>$page]));
        }
        return ['form'  => $form->createView(),'page'=>$page,'auth'=>$auth];
    }

    /**
     *  @Route("/getMsg", methods="POST")
     */
    public function getMsgAction(Request $req)
    {
        $data = $req->request->all();

        $rentalStation = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalStation')->find(1);
        $param = array(
            'sfzh' => $data['IdNumber'],  //身份证号
            'xm' => $data['name'],    //姓名
            'xb' => $data['sex'], //性别
            'mz' => $data['nation'],  //民族
            'lxdh' => $data['mobile'],    //联系电话
            'zz' => $data['address'], //住址
            'msmc' => $rentalStation->getName(),  //门店名称
            'msdh' => $rentalStation->getContactMobile(),    //门店电话
            'msdz' => $rentalStation->getStreet(),   //门店地址
            'qydm' => $rentalStation->getCompany()->getEnterpriseCode(),  //企业代码
            'qymc' =>  $rentalStation->getCompany()->getName(),   //企业名称
            'qydz' => $rentalStation->getArea()->getParent()->getParent()->getName().$rentalStation->getArea()->getParent()->getName().$rentalStation->getArea()->getName().$rentalStation->getCompany()->getAddress()  //企业地址
        );
        //dump($param);die;
        $url = "http://zulin.beijingyunshu.cn:11000/ReportWS/services/IReport?WSDL" ;

        //header("content-type:text/html;charset=utf-8");
	    libxml_disable_entity_loader(false);
        $client = new \SoapClient($url, array('cache_wsdl' => WSDL_CACHE_NONE));

//        code	int	必填	0：数据报送成功且当前承租人身份核实没有问题
//        XXX：正整数，业务类型的数据错误，租赁企业应根据错误类型进行处理，并重新进行必要的数据报送。
//        -XXX:负整数，当前承租人身份核实报警
//        message	String	必填	包含具体的错误信息和当前承租人身份核实的报警信息
        $arr = $client->sendData($param);
        $result = '';
        if( $arr->code == 0 ){
            $result = '承租人身份核实没有问题。';
        }else if( $arr->code == 1 ){
            $result = '承租人身份核实报警!!!。';
        }else{
            $result = '数据错误，请重新进行数据报送。';
        }
//        dump($arr);
        return new JsonResponse([
                'validateResult'    =>  $result
            ]);

    }


}
