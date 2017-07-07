<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 15/7/20
 * Time: 下午2:54
 */

namespace Auto\Bundle\AdminBundle\Controller;

use Auto\Bundle\ManagerBundle\Form\MemberType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Auto\Bundle\ManagerBundle\Form\AuthStep1Type;
use Auto\Bundle\ManagerBundle\Form\AuthStep2Type;

/**
 * @Route("/auth")
 */
class AuthController extends Controller {
    
    const PER_PAGE = 20;

    /**
     * @Route("/step1/{id}", methods="GET", name="auto_admin_auth_step1",
     * requirements={"id"="\d+"})
     * @Template()
     */
    public function step1Action($id)
    {
        $auth = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:AuthMember')
            ->find($id);


        $form = $this->createForm(new AuthStep1Type(), $auth, [
            'action' => $this->generateUrl('auto_admin_auth_step1_sumbit',["id"=>$id]),
            'method' => 'POST'
        ]);
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
        $authinfo = $this->get('auto_manager.member_helper')->get_license_auth_status($auth->getMember());

       // dump($auth);exit;
        return ['auth'=>$auth,'form'  => $form->createView(),'blacked'=>$blacked,'authinfo'=>$authinfo];
    }



    /**
     * @Route("/show/{id}", methods="GET", name="auto_admin_auth_show",
     * requirements={"id"="\d+"})
     * @Template()
     */
    public function showAction($id)
    {
        $auth = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:AuthMember')
            ->find($id);
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

        $authinfo = $this->get('auto_manager.member_helper')->get_license_auth_status($auth->getMember());

        return ['auth'=>$auth,'blacked'=>$blacked,'authinfo'=>$authinfo];
    }




    /**
     * @Route("/step1/{id}", methods="POST", name="auto_admin_auth_step1_sumbit",
     * requirements={"id"="\d+"})
     * @Template("AutoAdminBundle:Auth:step1.html.twig")
     */
    public function step1SubmitAction(Request $req,$id)
    {

        $auth = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:AuthMember')
            ->find($id);

        $form = $this->createForm(new AuthStep1Type(), $auth, [
            'action' => $this->generateUrl('auto_admin_car_create'),
            'method' => 'POST'
        ]);

        $member=$auth->getMember();
        $form->handleRequest($req);


        if ($form->isValid()) {
            $auth->setAuthTime(new \DateTime());
            $auth->setSubmitType(null);
            $auth->setIdNumber(null);
            $auth->setIDAddress(null);
            $auth->setDocumentNumber(null);
            $auth->setLicenseNumber(null);
            $auth->setLicenseAddress(null);
            $auth->setLicenseProvince(null);
            $auth->setLicenseCity(null);
            $auth->setLicenseStartDate(null);
            $auth->setLicenseEndDate(null);
            $auth->setLicenseValidYear(null);
            $auth->setValidateNewResult(null);
            $auth->setSubmitType(null);


            $member->setName(null);
            $member->setSex(null);
            $member->setNation(null);

            $man = $this->getDoctrine()->getManager();
            $man->persist($auth);
            $man->flush();

            if($auth->getLicenseImageAuthError()==0 && $auth->getIdHandImageAuthError()==0 && $auth->getIdImageAuthError()==0){
                return $this->redirect($this->generateUrl('auto_admin_auth_step2',["id"=>$id]));
            }
        }

        return $this->redirect($this->generateUrl('auto_admin_auth_show',["id"=>$id]));

    }









    /**
     * @Route("/step2/{id}", methods="GET", name="auto_admin_auth_step2",
     * requirements={"id"="\d+"})
     * @Template()
     */
    public function step2Action($id)
    {
        $auth = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:AuthMember')
            ->find($id);

        $form = $this->createFormBuilder()
            ->add('auth', new AuthStep2Type(),['data'=>$auth])
            ->add('member', new MemberType(),['data'=>$auth->getMember()])
            ->setAction( $this->generateUrl('auto_admin_auth_step2',["id"=>$id]))
            ->setMethod('POST')
            ->getForm();


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

      //  dump($auth);exit;
        return ['auth'=>$auth,'form'  => $form->createView(),"blacked"=>$blacked];
    }


    /**
     * @Route("/step2/{id}", methods="POST", name="auto_admin_auth_step2_submit",
     * requirements={"id"="\d+"})
     * @Template("AutoAdminBundle:Auth:step2.html.twig")
     */
    public function step2SubmitAction(Request $req,$id)
    {
        $auth = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:AuthMember')
            ->find($id);


        $form = $this->createFormBuilder()
            ->add('auth', new AuthStep2Type(),['data'=>$auth])
            ->add('member', new MemberType(),['data'=>$auth->getMember()])
            ->setAction( $this->generateUrl('auto_admin_auth_step2',["id"=>$id]))
            ->setMethod('POST')
            ->getForm();

        $form->handleRequest($req);
        if ($form->isValid()) {

            $man = $this->getDoctrine()->getManager();
            $auth->setAuthTime(new \DateTime());
            $man->persist($auth);
            $man->flush();

            // 好友邀请 发放奖励
            // 只有认证身份证才给奖励
            // 
            // 邀请人 被邀请人都认证通过
            // 邀请人与被邀请人的身份证不能一致
            // 被邀人的身份id 没有被发过奖励
            // 

            if ($auth->getIdNumber()) {
               
                
                // 找到关联关系
                $relation =
                    $this
                        ->getDoctrine()
                        ->getRepository('AutoManagerBundle:Invite')
                        ->findOneBy(['inviteeMobile'=>$auth->getMember()->getMobile()])
                ;

                // 邀请人认证通过
                if (!empty($relation)) {
                

                    $inviter =
                        $this
                            ->getDoctrine()
                            ->getRepository('AutoManagerBundle:AuthMember')
                            ->findOneBy(['member'=>$relation->getInviter()]);


                    if ($inviter) {
                        
                        $inviterAuthStatus = $inviter->getStatus();

                        // 被邀请人是否认证成功`
                        $inviteeAuthStatus = $auth->getStatus();


                        if ($inviterAuthStatus == \Auto\Bundle\ManagerBundle\Entity\AuthMember::AUTH_SUCCESS &&
                            $inviteeAuthStatus == \Auto\Bundle\ManagerBundle\Entity\AuthMember::AUTH_SUCCESS) 
                        {
                            // 是否领过发过奖励了
                            $qb =
                                $this
                                    ->getDoctrine()
                                    ->getRepository('AutoManagerBundle:InviteReward')
                                    ->createQueryBuilder('i')
                                    ->select('i')
                                    ->join('i.invitee','a')
                            ;

                            $authIDNumber =
                                $qb
                                    ->join('i.relative','r')
                                    ->andWhere($qb->expr()->eq('r.inviter', ':inviter'))
                                    ->andWhere($qb->expr()->eq('r.inviteeMobile', ':mobile'))
                                    ->setParameter('inviter', $inviter->getMember())
                                    ->setParameter('mobile', $auth->getMember()->getMobile())
                                    ->getQuery()
                                    ->getOneorNullResult();

                            if (empty($authIDNumber)) {
                                
                                // 同一张身份证互邀不给奖励

                                if ($inviter->getIdNumber() != $auth->getIdNumber()) {



                                    // 反向邀请
                                    $qb =
                                        $this
                                            ->getDoctrine()
                                            ->getRepository('AutoManagerBundle:InviteReward')
                                            ->createQueryBuilder('i')
                                            ->select('i')
                                            ->join('i.invitee','a')
                                    ;

                                    $invition =
                                        $qb
                                            ->join('i.relative','r')
                                            ->andWhere($qb->expr()->eq('r.inviter', ':inviter'))
                                            ->andWhere($qb->expr()->eq('r.inviteeMobile', ':mobile'))
                                            ->setParameter('inviter', $auth->getMember())
                                            ->setParameter('mobile', $inviter->getMember()->getMobile())
                                            ->getQuery()
                                            ->getOneorNullResult();

                                    if (empty($invition)) {
                                        
                                        // 获取奖励金额
                                        $activity =
                                            $this
                                                ->getDoctrine()
                                                ->getRepository('AutoManagerBundle:InviteActivity')
                                                ->findAll();

                                        if ($activity) {
                                            
                                            $activity = array_shift($activity);

                                            // 发放余额
                                            $inviter->getMember()->setWallet($inviter->getMember()->getWallet() + $activity->getCashBack());
                                            $man->persist($inviter);
                                            $man->flush();

                                            // 发放记录
                                            $operate = $this->getDoctrine()
                                                ->getRepository('AutoManagerBundle:RechargeOperate')
                                                ->find(6);

                                            $record = new \Auto\Bundle\ManagerBundle\Entity\RechargeRecord();
                                            $record->setCreateTime(new \DateTime());
                                            $record->setMember($inviter->getMember());
                                            $record->setOperate($operate);
                                            $record->setWalletAmount($inviter->getMember()->getWallet() + $activity->getCashBack());
                                            $record->setAmount($activity->getCashBack());
                                            $record->setRemark($operate->getName());
                                            $man->persist($record);
                                            $man->flush();

                                            // 保存邀请充值记录
                                            $inviteRecord = new \Auto\Bundle\ManagerBundle\Entity\InviteReward();
                                            $inviteRecord->setInvitee($auth);
                                            $inviteRecord->setRelative($relation);
                                            $inviteRecord->setRechargeRecord($record);

                                            $man->persist($inviteRecord);
                                            $man->flush();
                                        }
                                        

                                    }
                                    
                                    
                                }
                            }
                        }
                    }
                }
            }
            


            return $this->redirect($this->generateUrl('auto_admin_authmember_auth_list'));
        }

        return ['auth'=>$auth,'form'  => $form->createView()];
    }


    /**
     * @Route("/changJingData", methods="POST")
     */

    public function changJingDataAction(Request $req){
        $base_route = $this->container->getParameter("base_route");
        $image = $base_route.$req->request->get('image');
        $type = $req->request->getInt('type');

        if($type==1){

            $result = $this->get("auto_manager.chang_jing_helper")->IDCardOCR($image);
        }elseif($type==2){
            $result = $this->get("auto_manager.chang_jing_helper")->DriverOCR($image);
        }

        return new JsonResponse(
            ['errorCode'=>0,'result'=>$result]
        );
    

    }

    /**
     * @Route("/changJingAuth", methods="POST")
     */

    //type=1 验证手机号  type=2 验证犯罪记录  type=3 验证驾照 type=4 验证身份证
    public function changJingAuthAction(Request $req){

        $name = $req->request->get('name');
        $IDnumber = $req->request->get('IDnumber');
        $type = $req->request->getInt('type');
        if($type==1){
            $mobile = $req->request->get('mobile');
            $result = $this->get("auto_manager.chang_jing_helper")->phone($name,$mobile,$IDnumber);
        }elseif($type==2){
            $mobile = $req->request->get('mobile');
            $result = $this->get("auto_manager.chang_jing_helper")->crime($name,$mobile,$IDnumber);
        }elseif($type==3){
            $province = $req->request->get('province');
            $city = $req->request->get('city');
            $result = $this->get("auto_manager.chang_jing_helper")->driver($name,$IDnumber,$province,$city);
        }elseif($type==4){
            $result = $this->get("auto_manager.chang_jing_helper")->IDnumber($name,$IDnumber);
        }

        return new JsonResponse(
            ['errorCode'=>0,'result'=>$result]
        );


    }



    /**
     * @Route("/reset/{id}", methods="GET", name="auto_admin_auth_reset",
     * requirements={"id"="\d+"})
     * @Template()
     */
    public function resetAction($id)
    {
        $auth = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:AuthMember')
            ->find($id);
        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:BlackList')
                ->createQueryBuilder('a')
                ->select('a')
                ->join('a.authMember','b')
        ;
        $blackeds=$qb
            ->Where($qb->expr()->eq('b.IDNumber', ':IDNumber'))
            ->setParameter('IDNumber', $auth->getIDNumber())
            ->getQuery()
            ->getResult()
        ;

        $man = $this->getDoctrine()->getManager();
        foreach($blackeds  as $value ){
            $man->remove($value);
            $man->flush();
        }
        $man->remove($auth);
        $man->flush();
        return $this->redirect($this->generateUrl('auto_admin_authmember_auth_list'));
    }



}
