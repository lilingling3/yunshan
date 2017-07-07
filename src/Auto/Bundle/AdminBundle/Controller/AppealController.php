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
use Auto\Bundle\ManagerBundle\Form\AppealType;


/**
 * @Route("/appeal")
 */
class AppealController extends Controller
{

    const PER_PAGE = 20;

    /**
     * @Route("/list/{page}", methods="GET", name="auto_admin_appeal_list",
     * requirements={"page"="\d+"},
     * defaults={"page"=1})
     * @Template()
     */
    public function listAction(Request $req, $page = 1)
    {
        $status = $req->query->get('status');
        $idNumber = $req->query->get('idNumber');
        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:Appeal')
                ->createQueryBuilder('a')
                ->select('a')
                ->join('a.blackList','ab')

        ;
        if($idNumber){
            $qb ->join('a.blackList','b')
                ->select('b')
                ->join('b.authMember','am')
                ->andWhere($qb->expr()->eq('am.IDNumber', ':idNumber'))
                ->setParameter('idNumber', $idNumber);
        }
        if($status){
            if($status==1){
                $qb
                    ->andWhere($qb->expr()->isNull('a.handleTime'))
                    ->andWhere($qb->expr()->eq('a.status', ':status'))
                    ->setParameter('status', 0);

            }elseif($status==2){
                $qb
                    ->andWhere($qb->expr()->isNotNull('a.handleTime'))
                    ->andWhere($qb->expr()->eq('a.status', ':status'))
                    ->setParameter('status', 0);
            }elseif($status==3){
                $qb
                    ->andWhere($qb->expr()->isNotNull('a.handleTime'))
                    ->andWhere($qb->expr()->eq('a.status', ':status'))
                    ->setParameter('status', 1);
            }


        }
        $appealsOrigin =
            new Paginator(
                $qb
                    ->select('a')
                    ->orderBy('a.id', 'DESC')
                    ->setMaxResults(self::PER_PAGE)
                    ->setFirstResult(self::PER_PAGE * ($page - 1))
            );
        if(count( $appealsOrigin)==0){
            $appeals=null;
        }else{
            $appeals=$appealsOrigin;
    }
       //
        $today=new \DateTime();
        $total = ceil(count( $appeals) / self::PER_PAGE);
        return ['appeals'=>$appeals,'page'=>$page,'total'=>$total,'idNumber'=>$idNumber,'status'=>$status,'today'=>$today];
    }

    /**
     * @Route("/search", methods="POST", name="auto_admin_appeal_search")
     * @Template()
     */
    public function searchAction(Request $req){
        return $this->redirect(
            $this->generateUrl(
                'auto_admin_appeal_list',
                [
                    'status' => $req->request->getInt('status'),
                    'idNumber' => $req->request->get('idNumber'),
                ]
            )
        );

    }

    /**
     * @Route("/new", methods="GET", name="auto_admin_appeal_new")
     * @Template()
     */
    public function newAction(Request $req)
    {
        $mobile = $req->query->get('mobile');
        if($mobile){
            $member=$this->getDoctrine()
                ->getRepository('AutoManagerBundle:Member')
                ->findOneBy(["mobile"=>$mobile]);
            $auth = $this->getDoctrine()
                ->getRepository('AutoManagerBundle:AuthMember')
                ->findOneBy(["member"=>$member]);
            $qbl =
                $this
                    ->getDoctrine()
                    ->getRepository('AutoManagerBundle:BlackList')
                    ->createQueryBuilder('bl')
            ;
            $blackStatus=$qbl
                ->select('bl')
                ->andWhere($qbl->expr()->eq('bl.authMember', ':authMember'))
                ->setParameter('authMember', $auth)
                ->andWhere($qbl->expr()->orX(
                    $qbl->expr()->gt('bl.endTime', ':today'),
                    $qbl->expr()->isNull('bl.endTime')
                ))
                ->setParameter('today', new \DateTime())
                ->getQuery()
                ->getOneorNullResult();
            $appealStatus=$this->getDoctrine()
                ->getRepository('AutoManagerBundle:Appeal')
                ->findOneBy(["blackList"=>$blackStatus]);
            if($blackStatus){
                $createTime = json_decode(json_encode($blackStatus->getCreateTime()),TRUE);
                $endTime = json_decode(json_encode($blackStatus->getEndTime()),TRUE);
                $starttime =strtotime($createTime['date']);
                $endtime =strtotime($endTime['date']);
                $timediff = $endtime-$starttime;
                $blackdays = ceil($timediff/86400);
            }else{
                $blackdays=null;
            }


        }else{
            $member=null;
            $auth=null;
            $blackStatus=null;
            $blackdays=null;
            $appealStatus=[];
        }
        $form = $this->createFormBuilder()
            ->add('appeal', new AppealType())
            ->add('id', 'text')
            ->setAction($this->generateUrl('auto_admin_appeal_create'))
            ->setMethod("POST")
            ->getForm();

        return ["member"=>$member,"auth"=>$auth,"mobile"=>$mobile,'form'  => $form->createView(),"blackStatus"=>$blackStatus,
            "blackdays"=>$blackdays,"appealStatus"=> $appealStatus];
    }

    /**
     * @Route("/create", methods="POST", name="auto_admin_appeal_create")
     * @Template()
     */
    public function createAction(Request $req){
        $appeal = new \Auto\Bundle\ManagerBundle\Entity\Appeal();
        $form = $this->createFormBuilder()
            ->add('appeal', new AppealType(),['data'=>$appeal])
            ->add('id', 'text')
            ->setAction($this->generateUrl('auto_admin_appeal_create'))
            ->setMethod("POST")
            ->getForm();

        $form->handleRequest($req);

        if ($form->isValid()) {

            $data = $form->getData();

            $appeal = $data['appeal'];
            $id = $data['id'];
            $blacklist = $this->getDoctrine()
                ->getRepository('AutoManagerBundle:BlackList')
                ->findOneBy(['id'=>$id]);

            $b = new \Auto\Bundle\ManagerBundle\Entity\Appeal();
            $b
                ->setStatus(0)
                ->setReason($appeal->getReason())
                ->setBlackList($blacklist)
            ;
            $man = $this->getDoctrine()->getManager();
            $man->persist($b);
            $man->flush();




            return $this->redirect($this->generateUrl('auto_admin_appeal_list'));

        }}


    /**
     * @Route("/new/search", methods="POST", name="auto_admin_appeal_new_search")
     * @Template()
     */
    public function newSearchAction(Request $req){
        return $this->redirect(
            $this->generateUrl(
                'auto_admin_appeal_new',
                [

                    'mobile' => $req->request->get('mobile'),
                ]
            )
        );

    }




    /**
     * @Route("/show/{id}", methods="GET", name="auto_admin_appeal_show")
     * @Template()
     */
    public function showAction($id)
    {
        $appeal=$this->getDoctrine()
            ->getRepository('AutoManagerBundle:Appeal')
            ->find($id);


        $member=  $appeal->getBlackList() ;
        $createTime = json_decode(json_encode($member->getCreateTime()),TRUE);
        $endTime = json_decode(json_encode($member->getEndTime()),TRUE);
        $starttime =strtotime($createTime['date']);
        $endtime =strtotime($endTime['date']);
        $timediff = $endtime-$starttime;
        $blackdays = ceil($timediff/86400);

        $qbam =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:AuthMember')
                ->createQueryBuilder('am')
        ;
        $authNumbers=$qbam ->select('am')
            ->andWhere($qbam->expr()->eq('am.licenseAuthError', ':licenseautherror'))
            ->andWhere($qbam->expr()->gte('am.licenseEndDate', ':today'))
            ->andWhere($qbam->expr()->eq('am.IDNumber', ':IDNumber'))
            ->setParameter('today', new \DateTime())
            ->setParameter('IDNumber', $appeal->getBlackList()->getAuthMember()->getIDNumber())
            ->setParameter('licenseautherror', 0)
            ->getQuery()
            ->getResult();


        $today=new \DateTime();
        return ["member"=>$member,"blackdays"=>$blackdays,"appeal"=>$appeal,"authNumbers"=>$authNumbers,"today"=>$today];
    }


    /**
     * @Route("/fail/{id}", methods="GET", name="auto_admin_appeal_fail",requirements={"id"="\d+"})
     */
    public function failAction($id)
    {
        $date = (new \DateTime((new \DateTime('+0 days'))->format('Y-m-d H:i:s')));
        $appeal = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Appeal')
            ->find($id);
        $appeal->setStatus(0);
        $appeal->setHandleTime($date);
        $man = $this->getDoctrine()->getManager();
        $man->persist($appeal);
        $man->flush();

        return $this->redirect($this->generateUrl('auto_admin_appeal_list'));
    }


    /**
     * @Route("/appealDel/{id}", methods="GET", name="auto_admin_appeal_blacklist_delete",requirements={"id"="\d+"})
     */
    public function appealDeleteAction($id)
    {
        $black = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:BlackList')
            ->find($id);

        $appeals=$this->getDoctrine()
            ->getRepository('AutoManagerBundle:Appeal')
            ->findBy(["blackList"=>$black]);
      
        if($appeals){
            foreach($appeals as $appeal ){
                if(!$appeal->getHandleTime()){
                    $appeal->setHandleTime(new \DateTime());
                    $appeal->setStatus(1);
                }
            }
        }
        $black->setEndTime(new \DateTime());
        $man = $this->getDoctrine()->getManager();

        $man->persist($black);
        $man->flush();

        return $this->redirect($this->generateUrl('auto_admin_appeal_list'));
    }


}


