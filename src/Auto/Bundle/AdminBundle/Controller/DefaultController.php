<?php

namespace Auto\Bundle\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\SecurityContext;
use Auto\Bundle\ManagerBundle\Entity\AuthMember;
use Auto\Bundle\ManagerBundle\Form\AuthMemberType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Auto\Bundle\ManagerBundle\Entity\RentalCar;
use Auto\Bundle\ManagerBundle\Form\RentalCarType;
use Doctrine\ORM\EntityManager;
use Auto\Bundle\ManagerBundle\Entity\Member;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;


class DefaultController extends Controller
{
    const PER_PAGE = 20;

    /**
     * @Route("/login", methods="GET", name="auto_admin_login")
     * @Template()
     */
    public function loginAction()
    {

        return [];
    }


    /**
     * @Route("/login", methods="POST", name="auto_admin_login_verify")
     * @Template("AutoAdminBundle:Default:login.html.twig")
     */
    public function loginVerifyAction(Request $req)
    {
        $mobile = $req->request->getInt('mobile');
        $code = $req->request->getInt('code');

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
            return $this->render(
                "AutoAdminBundle:Default:message.html.twig",
                ['message' => '验证码错误']);
        }

        if($SMSCode->getEndTime()<new \DateTime()){
            return $this->render(
                "AutoAdminBundle:Default:message.html.twig",
                ['message' => '验证码已过期']);
        }

        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->findOneBy(['mobile' => $mobile]);

        $this->authenticateUser($member);

        return $this->redirect($this->generateUrl('auto_admin_index'));
    }

    private function authenticateUser(Member $user)
    {
        $providerKey = 'mobile';
        $token = new UsernamePasswordToken($user, null, $providerKey, $user->getRoles());

        $this->container->get('security.context')->setToken($token);
    }

    /**
     * @Route("/index", methods="GET", name="auto_admin_index")
     * @Template()
     */

    public function indexAction(Request $req)
    {
        $auth_times = $this->getDoctrine()
        ->getRepository('AutoManagerBundle:AuthMember')
        ->findBy(['authTime'=>null]);

        $qb= $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->createQueryBuilder('r')
            ->select('r')
            ->leftJoin('r.online','o');
        $status =
            $qb->andWhere($qb->expr()->orX(
                $qb->expr()->eq('o.status', ':status'),
                $qb->expr()->isNull('r.online'))
            )
            ->setParameter('status',0)
            ->getQuery()
            ->getResult() ;



        $Illegalcars =$this->getDoctrine()
            ->getRepository('AutoManagerBundle:IllegalRecord')
            ->findBy(['handleTime'=>null]);

        //黑名单
        $qb =$this
            ->getDoctrine()
            ->getRepository('AutoManagerBundle:Appeal')
            ->createQueryBuilder('ap');
        $black_members =$qb ->where($qb->expr()->isNull('ap.handleTime'))
            ->getQuery()
            ->getResult() ;

        $qbor =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:OperateRecord')
                ->createQueryBuilder('o')
                ->select('o');
        $operateRecords=$qbor
            ->orderBy('o.createTime', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();





        $waiting = count($auth_times) ;
        $offline = count($status) ;
        $Illegalcar=count($Illegalcars);
        $appeal=count($black_members);
        return ['waiting'=>$waiting,'offline'=>$offline,'Illegal'=>$Illegalcar,"black_members"=>$appeal,
            "operateRecords"=> $operateRecords];
    }




}
