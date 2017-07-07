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
use Auto\Bundle\ManagerBundle\Form\BlackListType;
use Auto\Bundle\ManagerBundle\Form\AppealType;
/**
 * @Route("/blacklist")
 */
class BlackListController extends Controller
{

    const PER_PAGE = 20;

    /**
     * @Route("/list/{page}", methods="GET", name="auto_admin_black_list",
     * requirements={"page"="\d+"},
     * defaults={"page"=1})
     * @Template()
     */
    public function listAction(Request $req, $page = 1)
    {
        $status = $req->query->get('status');
        $idNumber = $req->query->get('idNumber');
        $mobile = $req->query->get('mobile');

        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:BlackList')
                ->createQueryBuilder('b')
                ->select('b')
                ->join('b.authMember','a')
        ;
        if($idNumber){
            $qb
                ->andWhere($qb->expr()->eq('a.IDNumber', ':idNumber'))
                ->setParameter('idNumber', $idNumber);
        }
        if($mobile){
            $qb
                ->join('a.member','m')
                ->andWhere($qb->expr()->eq('m.mobile', ':mobile'))
                ->setParameter('mobile', $mobile);
        }
        if($status){
            $qb
                ->andWhere($qb->expr()->eq('b.reason', ':status'))
                ->setParameter('status', $status);
        }
        $blacks_origin =
            new Paginator(
                $qb
                    ->select('b')
                    ->andWhere($qb->expr()->orX(
                        $qb->expr()->gt('b.endTime', ':today'),
                        $qb->expr()->isNull('b.endTime')
                    ))
                    ->setParameter('today', new \DateTime())
                    ->orderBy('b.id', 'DESC')
                    ->setMaxResults(self::PER_PAGE)
                    ->setFirstResult(self::PER_PAGE * ($page - 1))
            );
       $number=count($blacks_origin);
        if($number==0){
            $blacks=null;

        }else{
            $blacks= $blacks_origin;

        }

        $total = ceil(count( $blacks) / self::PER_PAGE);
        return ['blacks'=> $blacks,'page'=>$page,'total'=>$total,'idNumber'=>$idNumber,'status'=>$status,'mobile'=>$mobile];
    }

    /**
     * @Route("/search", methods="POST", name="auto_admin_blacklist_search")
     * @Template()
     */
    public function searchAction(Request $req){
        return $this->redirect(
            $this->generateUrl(
                'auto_admin_black_list',
                [
                    'status' => $req->request->getInt('status'),
                    'idNumber' => $req->request->get('idNumber'),
                    'mobile' => $req->request->get('mobile'),
                ]
            )
        );

    }

    /**
     * @Route("/new", methods="GET", name="auto_admin_blacklist_new")
     * @Template()
     */
    public function newAction(Request $req)
    {
        $mobile = $req->query->get('mobile');


        if($mobile){
            $member=$this->getDoctrine()
                ->getRepository('AutoManagerBundle:Member')
                ->findOneBy(["mobile"=>$mobile]);


            $qbam =
                $this
                    ->getDoctrine()
                    ->getRepository('AutoManagerBundle:AuthMember')
                    ->createQueryBuilder('am')
            ;
            $auth=$qbam ->select('am')
                ->andWhere($qbam->expr()->eq('am.member', ':member'))
                // ->andWhere($qbam->expr()->eq('am.licenseImageAuthError', ':licenseImageAuthError'))
                // ->andWhere($qbam->expr()->eq('am.idImageAuthError', ':idImageAuthError'))
                // ->andWhere($qbam->expr()->eq('am.idHandImageAuthError', ':idHandImageAuthError'))
                // ->andWhere($qbam->expr()->eq('am.mobileCallError', ':mobileCallError'))
                // ->andWhere($qbam->expr()->eq('am.validateError', ':validateError'))
//                ->andWhere($qbam->expr()->gte('am.licenseEndDate', ':today'))
//                ->setParameter('today', new \DateTime())
                ->setParameter('member', $member)
                // ->setParameter('licenseImageAuthError', 0)
                // ->setParameter('idImageAuthError', 0)
                // ->setParameter('idHandImageAuthError', 0)
                // ->setParameter('mobileCallError', 0)
                // ->setParameter('validateError', 0)
                ->getQuery()
                ->getOneorNullResult();

            if($auth){

                $qbam2 =
                    $this
                        ->getDoctrine()
                        ->getRepository('AutoManagerBundle:AuthMember')
                        ->createQueryBuilder('am2')
                ;
                $authNumbers=$qbam2 ->select('am2')
                    ->andWhere($qbam2->expr()->eq('am2.licenseImageAuthError', ':licenseImageAuthError'))
                    ->andWhere($qbam2->expr()->eq('am2.idImageAuthError', ':idImageAuthError'))
                    ->andWhere($qbam2->expr()->eq('am2.idHandImageAuthError', ':idHandImageAuthError'))
                    // ->andWhere($qbam2->expr()->eq('am2.mobileCallError', ':mobileCallError'))
                    ->andWhere($qbam2->expr()->eq('am2.validateError', ':validateError'))
                    ->andWhere($qbam2->expr()->gte('am2.licenseEndDate', ':today'))
                    ->andWhere($qbam2->expr()->eq('am2.IDNumber', ':IDNumber'))
                    ->setParameter('today', new \DateTime())
                    ->setParameter('IDNumber', $auth->getIDNumber())
                    ->setParameter('licenseImageAuthError', 0)
                    ->setParameter('idImageAuthError', 0)
                    ->setParameter('idHandImageAuthError', 0)
                    // ->setParameter('mobileCallError', 0)
                    ->setParameter('validateError', 0)
                    ->getQuery()
                    ->getResult();

                $qbl =
                    $this
                        ->getDoctrine()
                        ->getRepository('AutoManagerBundle:BlackList')
                        ->createQueryBuilder('bl')
                        ->select('bl')
                        ->join('bl.authMember','bam')
                ;
                $blackStatus=$qbl

                    ->andWhere($qbl->expr()->eq('bam.IDNumber', ':IDNumber'))
                    ->setParameter('IDNumber', $auth->getIDNumber())
                    ->andWhere($qbl->expr()->orX(
                        $qbl->expr()->gt('bl.endTime', ':today'),
                        $qbl->expr()->isNull('bl.endTime')
                    ))
                    ->setParameter('today', new \DateTime())
                    ->getQuery()
                    ->getOneorNullResult();

            }else{
                $blackStatus=null;
                $authNumbers=null;
            }

            $qb =
                $this
                    ->getDoctrine()
                    ->getRepository('AutoManagerBundle:Appeal')
                    ->createQueryBuilder('a')
            ;
            $appeals=$qb
                ->select('a')
                ->andWhere($qb->expr()->eq('a.blackList', ':blackList'))
                ->setParameter('blackList', $blackStatus)
                ->orderBy('a.createTime', 'ASC')
                ->getQuery()
                ->getResult()
            ;

            if($blackStatus){

                $createTime = json_decode(json_encode($blackStatus->getCreateTime()),TRUE);
                $endTime = json_decode(json_encode($blackStatus->getEndTime()),TRUE);
                $starttime =strtotime($createTime['date']);
                $endtime =strtotime($endTime['date']);
                $timediff = $endtime-$starttime;
                $blackdays = ceil($timediff/86400);}else{
                $blackdays =null;
            }


        }else{
            $member=null;
            $auth=null;
            $blackStatus=null;
            $blackdays=null;
            $appeals=[];
            $authNumbers=[];
        }
        $form = $this->createFormBuilder()
            ->add('blacklist', new BlackListType())
            ->add('limit', 'choice',array('choices' => array('0' => '请选择','7' => '7天', '15' => '15天', '30' => '30天'
            , '90' => '90天' , '720' => '永久')))
            ->add('IDNumber', 'text')
            ->add('mobile', 'text')
            ->setAction($this->generateUrl('auto_admin_blacklist_create'))
            ->setMethod("POST")
            ->getForm();

        return ["member"=>$member,"auth"=>$auth,"mobile"=>$mobile,'form'  => $form->createView(),"blackStatus"=>$blackStatus,"blackdays"=>$blackdays,"appeals"=>$appeals,'authNumbers'=>$authNumbers];
    }

    /**
     * @Route("/create", methods="POST", name="auto_admin_blacklist_create")
     * @Template()
     */
    public function createAction(Request $req){
        $blacklist = new \Auto\Bundle\ManagerBundle\Entity\BlackList();
        $form = $this->createFormBuilder()
            ->add('blacklist', new BlackListType(),['data'=>$blacklist])
            ->add('limit', 'choice',array('choices' => array('0' => '请选择','7' => '7天', '15' => '15天', '30' => '30天'
            , '90' => '90天' , '720' => '永久')))
            ->add('IDNumber', 'text')
            ->add('mobile', 'text')
            ->setAction($this->generateUrl('auto_admin_blacklist_create'))
            ->setMethod("POST")
            ->getForm();

        $form->handleRequest($req);

        if ($form->isValid()) {

            $data = $form->getData();

            $blacklist = $data['blacklist'];
            $limit = $data['limit'];
            $IDNumber = $data['IDNumber'];
            $mobile = $data['mobile'];
            $qb =
                $this
                    ->getDoctrine()
                    ->getRepository('AutoManagerBundle:AuthMember')
                    ->createQueryBuilder('am')
                    ->select('am')
                    ->join('am.member','m')
            ;
            $authMember = $qb
                ->andWhere($qb->expr()->eq('m.mobile', ':mobile'))
                ->andWhere($qb->expr()->eq('am.IDNumber', ':IDNumber'))
                ->setParameter('IDNumber', $IDNumber)
                ->setParameter('mobile', $mobile)
                ->getQuery()
                ->getSingleResult()
            ;

            $b = new \Auto\Bundle\ManagerBundle\Entity\BlackList();

            $date = (new \DateTime((new \DateTime('+'.$limit.' days'))->format('Y-m-d')));
            if($limit==720){
                $b
                    ->setEndTime(null)
                    ->setReason($blacklist->getReason())
                    ->setDetail($blacklist->getDetail())
                    ->setAuthMember( $authMember)
                ;
            }else{
                $b
                    ->setEndTime($date)
                    ->setReason($blacklist->getReason())
                    ->setDetail($blacklist->getDetail())
                    ->setAuthMember( $authMember)
                ;
            }

            $man = $this->getDoctrine()->getManager();
            $man->persist($b);
            $man->flush();



            return $this->redirect($this->generateUrl('auto_admin_black_list'));

        }}


    /**
     * @Route("/new/search", methods="POST", name="auto_admin_blacklist_new_search")
     * @Template()
     */
    public function newSearchAction(Request $req){
        return $this->redirect(
            $this->generateUrl(
                'auto_admin_blacklist_new',
                [

                    'mobile' => $req->request->get('mobile'),
                ]
            )
        );

    }




    /**
     * @Route("/show/{id}", methods="GET", name="auto_admin_blacklist_show")
     * @Template()
     */
    public function showAction($id)
    {
        $member=$this->getDoctrine()
            ->getRepository('AutoManagerBundle:BlackList')
            ->find($id);

        $createTime = json_decode(json_encode($member->getCreateTime()),TRUE);
        $endTime = json_decode(json_encode($member->getEndTime()),TRUE);
        $starttime =strtotime($createTime['date']);
        $endtime =strtotime($endTime['date']);
        $timediff = $endtime-$starttime;
        $blackdays = ceil($timediff/86400);

        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:Appeal')
                ->createQueryBuilder('a')
        ;
        $appeals=$qb
            ->select('a')
            ->andWhere($qb->expr()->eq('a.blackList', ':blackList'))
            ->setParameter('blackList', $member)
            ->orderBy('a.createTime', 'asc')
            ->getQuery()
            ->getResult()
        ;
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
            ->setParameter('IDNumber', $member->getAuthMember()->getIDNumber())
            ->setParameter('licenseautherror', 0)
            ->getQuery()
            ->getResult();


        $form = $this->createFormBuilder()
            ->add('appeal', new AppealType())
            ->add('id', 'text')
            ->setAction($this->generateUrl('auto_admin_blacklist_appeal_create'))
            ->setMethod("POST")
            ->getForm();

        return ["member"=>$member,"blackdays"=>$blackdays,"appeals"=>$appeals,'n'=>count($appeals),'form'  => $form->createView(),'authNumbers'=>$authNumbers];
    }

    /**
     * @Route("/blackAppealCreate", methods="POST", name="auto_admin_blacklist_appeal_create")
     * @Template()
     */
    public function blackAppealCreateAction(Request $req){
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



            return $this->redirect($this->generateUrl('auto_admin_black_list'));

        }}

    /**
     * @Route("/del/{id}", methods="GET", name="auto_admin_blacklist_delete",requirements={"id"="\d+"})
     */
    public function deleteAction($id)
    {
        $man = $this->getDoctrine()->getManager();
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

        return $this->redirect($this->generateUrl('auto_admin_black_list'));
    }

    /**
     * @Route("/blackAppealFail/{id}", methods="GET", name="auto_admin_black_appeal_fail",requirements={"id"="\d+"})
     */
    public function blackAppealFailAction($id)
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

        return $this->redirect($this->generateUrl('auto_admin_black_list'));
    }


}


