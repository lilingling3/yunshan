<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 15/9/14
 * Time: 下午6:04
 */

namespace Auto\Bundle\AdminBundle\Controller;
use Auto\Bundle\ManagerBundle\Entity\AuthMember;
use Auto\Bundle\ManagerBundle\Form\AuthMemberType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Auto\Bundle\ManagerBundle\Entity\Area;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @Route("/member")
 */
class MemberController extends Controller {

    const PER_PAGE = 20;

    /**
     * @Route("/auth/list/{page}", methods="GET", name="auto_admin_member_auth_list",
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
                ->andWhere(
                    $qb->expr()->neq('m.licenseAuthError', ':error')
                )

                ->andWhere($qb->expr()->isNotNull('m.authTime'))

                ->setParameter('error', 0);
            ;
        }

        if($status == \Auto\Bundle\ManagerBundle\Entity\AuthMember::AUTH_SUCCESS){
            $qb
                ->andWhere(
                    $qb->expr()->eq('m.licenseAuthError', ':error')
                )

                ->andWhere(
                    $qb->expr()->gt('m.licenseEndDate', ':time')
                )

                ->setParameter('time', (new \DateTime()))


                ->setParameter('error', 0);
            ;
        }

        if($status == \Auto\Bundle\ManagerBundle\Entity\AuthMember::AUTH_LICENSE_EXPIRE){
            $qb
                ->andWhere(
                    $qb->expr()->isNotNull('m.licenseEndDate')
                )
                ->andWhere(
                    $qb->expr()->lt('m.licenseEndDate', ':time')
                )

                ->setParameter('time', (new \DateTime()));
            ;
        }


        $auth_members =
            new Paginator(
                $qb
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

        return ['auth_members'=>$auth_members,'time'=>$time,'page'=>$page,'total'=>$total,'status'=>$status];
    }



    /**
     * @Route("/auth/search", methods="POST", name="auto_admin_member_auth_search")
     * @Template()
     */
    public function authSearchAction(Request $req){
        return $this->redirect(
            $this->generateUrl(
                'auto_admin_member_auth_list',
                [
                    'status' => trim($req->request->getInt('status')),
                    'mobile' => trim($req->request->get('mobile')),
                    'name' => trim($req->request->get('name')),
                ]
            )
        );

    }





    /**
     * @Route("/auth/edit/{id}/{page}", methods="GET", name="auto_admin_member_auth_edit",defaults={"page"=1})
     * @Template()
     */
    public function authEditAction($id,$page,Request $req)
    {
        $auth = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:AuthMember')
            ->find($id);

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
            ->add('AuthMember', new AuthMemberType(),['data'=>$auth])
            ->add('name', 'text', ['data' => $member->getName(),'required' => false])
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
            ->setAction($this->generateUrl('auto_admin_member_auth_update', ['id' => $auth->getId()]))
            ->getForm();


        return ['form'  => $form->createView(),'auth'=>$auth,"blacked"=>$blacked];

    }

    /**
     * @Route("/auth/show/{id}/{page}", methods="GET", name="auto_admin_member_auth_show",defaults={"page"=1})
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
        //var_dump($blacked);
        return ['auth'=>$auth,'member'=>$member,"today"=>$today,"blacked"=>$blacked];

    }


    /**
     * @Route("/auth/edit/{id}/{page}", methods="POST", name="auto_admin_member_auth_update",defaults={"page"=1},
     * requirements={"id"="\d+"})
     * @Template("AutoAdminBundle:Member:authEdit.html.twig")
     */
    public function updateAction(Request $req, $id,$page)
    {
        $auth = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:AuthMember')
            ->find($id);

        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->find($auth->getMember()->getId());

        $form = $this->createFormBuilder()
            ->add('AuthMember', new AuthMemberType(),['data'=>$auth])
            ->add('name', 'text', ['data' => $member->getName(),'required' => false])
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
            ->setAction($this->generateUrl('auto_admin_member_auth_update', ['id' => $auth->getId()]))
            ->getForm();

        $form->handleRequest($req);
        if ($form->isValid()) {

            $data = $form->getData();

            $auth = $data['AuthMember'];

            if($data['name'] != $member->getName()){
                $auth->getMember()->setName($data['name']);
            }
            $auth->getMember()->setSex($data['sex']);
            $auth->getMember()->setAddress($data['address']);
            $auth->getMember()->setNation($data['nation']);
            $auth->setAuthTime(new \DateTime());
            $man = $this->getDoctrine()->getManager();
            $man->persist($auth);
            $man->flush();

            $auth_license = $this->get("auto_manager.member_helper")->get_license_auth_status($auth->getMember());


            if($auth_license['status']==\Auto\Bundle\ManagerBundle\Entity\AuthMember::AUTH_SUCCESS||$auth_license['status']==\Auto\Bundle\ManagerBundle\Entity\AuthMember::AUTH_FAILED){

                $this->get("auto_manager.sms_helper")->add(
                    $auth->getMember()->getMobile(),
                    $this->get("auto_manager.member_helper")->get_auth_error_message($auth->getLicenseAuthError()));

                $message = new \Auto\Bundle\ManagerBundle\Entity\Message();
                $message->setContent($this->get("auto_manager.member_helper")->get_auth_error_message
                ($auth->getLicenseAuthError(),0))
                    ->setKind(1)
                    ->setMember($member)
                    ->setStatus(\Auto\Bundle\ManagerBundle\Entity\Message::MESSAGE_UNREAD)
                ;
                $man = $this->getDoctrine()->getManager();
                $man->persist($message);
                $man->flush();
            }



            return $this->redirect($this->generateUrl('auto_admin_member_auth_list',['page'=>$page]));
        }
        return ['form'  => $form->createView(),'page'=>$page,'auth'=>$auth];
    }

    /**
     * @Route("/getName", methods="POST")
     */
    public function getNameByMobileAction(Request $req)
    {
        $data = $req->request->all();
        $mobile = $data['mobile'];
        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->findOneBy(['mobile'=>$mobile]);

        $name = empty($member) ? '' : $member->getName();
        return new JsonResponse(['name'=>$name]);

    }






}