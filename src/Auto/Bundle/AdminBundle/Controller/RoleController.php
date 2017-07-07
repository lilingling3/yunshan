<?php
/**
 * Created by PhpStorm.
 * User: liyandong
 * Date: 16/6/20
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
 * @Route("/role")
 */
class RoleController extends Controller {

    const PER_PAGE = 20;

    /**
     * @Route("/list/{page}", methods="GET", name="auto_admin_role_list",
     * requirements={"page"="\d+"},
     * defaults={"page"=1})
     * @Template()
     */
    public function listAction(Request $req,$page = 1)
    {
        $mobile = $req->query->get('mobile');
        $name = $req->query->get('name');
        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:Member')
                ->createQueryBuilder('m')
        ;
        if($mobile){
            $qb
                ->andWhere($qb->expr()->eq('m.mobile', ':mobile'))
                ->setParameter('mobile', $mobile);
        }
        if($name){
            $qb
                ->andWhere($qb->expr()->eq('m.name', ':name'))
                ->setParameter('name', $name);
        }
        $members =
            new Paginator(
                $qb
                    ->select('m')
                    ->andWhere($qb->expr()->neq('m.roles', ':role'))
                    ->setParameter('role','["'.\Auto\Bundle\ManagerBundle\Entity\Member::ROLE_USER.'"]')
                    ->orderBy('m.id', 'ASC')
                    ->getQuery()
                    ->setMaxResults(self::PER_PAGE)
                    ->setFirstResult(self::PER_PAGE * ($page - 1))
            );

        $total = ceil(count($members) / self::PER_PAGE);
        $tempMember = array();
        foreach ($members as $key => $member) {
            $tempMember[$key]['id'] = $member->getId();
            $tempMember[$key]['name'] = $member->getName();
            $tempMember[$key]['mobile'] = $member->getMobile();
            $roles = $member->getRoles();
            $tempRoleArray = array();
            if(in_array(\Auto\Bundle\ManagerBundle\Entity\Member::ROLE_ADMIN,$roles)){
                $tempRoleArray[] = '管理员';
            }
            if(in_array(\Auto\Bundle\ManagerBundle\Entity\Member::ROLE_COO,$roles)){
                $tempRoleArray[] = '运营总监';
            }
            if(in_array(\Auto\Bundle\ManagerBundle\Entity\Member::ROLE_REGION_MANAGER,$roles)){
                $tempRoleArray[] = '大区经理';
            }
            if(in_array(\Auto\Bundle\ManagerBundle\Entity\Member::ROLE_OPERATE,$roles)){
                $tempRoleArray[] = '运营专员';
            }
            if(in_array(\Auto\Bundle\ManagerBundle\Entity\Member::ROLE_MARKET,$roles)){
                $tempRoleArray[] = '市场主管';
            }
            if(in_array(\Auto\Bundle\ManagerBundle\Entity\Member::ROLE_FINANCE,$roles)){
                $tempRoleArray[] = '财务主管';
            }
            if(in_array(\Auto\Bundle\ManagerBundle\Entity\Member::ROLE_SERVER,$roles)){
                $tempRoleArray[] = '客服主管';
            }
            $tempMember[$key]['roles'] = implode('、',$tempRoleArray);
        }


        return ['members'=>$tempMember,'page'=>$page,'total'=>$total,'mobile'=>$mobile,'name'=>$name];

    }


    /**
     * @Route("/new", methods="GET", name="auto_admin_role_add")
     * @Template()
     */
    public function newAction()
    {
        return [];
    }

    /**
     * @Route("/new", methods="POST", name="auto_admin_role_create")
     * @Template("AutoAdminBundle:Role:new.html.twig")
     */
    public function createAction(Request $req)
    {
        $form = $req->request->get('form');
        if(empty($form['roles'])){
            return $this->render(
                "AutoAdminBundle:Default:message.html.twig",
                ['message'=>'请设置角色!']
            );
        }
        if ($form) {
            $mobile = $form['mobile'];
            $member = $this->getDoctrine()
                ->getRepository('AutoManagerBundle:Member')
                ->findOneBy(['mobile'=>$mobile]);

            if(empty($member)){

                return $this->render(
                    "AutoAdminBundle:Default:message.html.twig",
                    ['message'=>'请先注册用户!']
                );
            }
            if($member->getName() == null){
                $member->setName($form['name']);
            }
            $roles = $form['roles'];
            $newRoles = array(\Auto\Bundle\ManagerBundle\Entity\Member::ROLE_USER);
            if(in_array('admin',$roles)){
//                if(in_array(\Auto\Bundle\ManagerBundle\Entity\Member::ROLE_ADMIN,$member->getRoles())){
//                    return $this->render(
//                        "AutoAdminBundle:Default:message.html.twig",
//                        ['message'=>'已经是管理员了!']
//                    );
//                }else{
                    $newRoles[] = \Auto\Bundle\ManagerBundle\Entity\Member::ROLE_ADMIN;
//                }
            }
            if(in_array('coo',$roles)){
                $newRoles[] = \Auto\Bundle\ManagerBundle\Entity\Member::ROLE_COO;
            }
            if(in_array('region_manager',$roles)){
                $newRoles[] = \Auto\Bundle\ManagerBundle\Entity\Member::ROLE_REGION_MANAGER;
            }
            if(in_array('operate',$roles)){
                $newRoles[] = \Auto\Bundle\ManagerBundle\Entity\Member::ROLE_OPERATE;
            }
            if(in_array('market',$roles)){
                $newRoles[] = \Auto\Bundle\ManagerBundle\Entity\Member::ROLE_MARKET;
            }
            if(in_array('finance',$roles)){
                $newRoles[] = \Auto\Bundle\ManagerBundle\Entity\Member::ROLE_FINANCE;
            }
            if(in_array('server',$roles)){
                $newRoles[] = \Auto\Bundle\ManagerBundle\Entity\Member::ROLE_SERVER;
            }

            $member->setRoles($newRoles);
            $man = $this->getDoctrine()->getManager();
            $man->persist($member);
            $man->flush();


            return $this->redirect($this->generateUrl('auto_admin_role_list'));
        }
        return $this->redirect($this->generateUrl('auto_admin_role_add'));
    }

    /**
     * @Route("/edit/{id}", methods="GET", name="auto_admin_role_edit")
     * @Template()
     */
    public function editAction($id)
    {
        $member=$this->getDoctrine()
            -> getRepository('AutoManagerBundle:Member')
            ->find($id);
        if(empty($member)){
            return $this->render(
                "AutoAdminBundle:Default:message.html.twig",
                ['message'=>'此用户不存在!']
            );
        }
        $rolesFlag = array(
            'admin'=>0,
            'coo'=>0,
            'region_manager'=>0,
            'operate'=>0,
            'finance'=>0,
            'server'=>0,
            'market'=>0
        );
        if(in_array(\Auto\Bundle\ManagerBundle\Entity\Member::ROLE_ADMIN,$member->getRoles())){
            $rolesFlag['admin'] = 1;
        }
        if(in_array(\Auto\Bundle\ManagerBundle\Entity\Member::ROLE_COO,$member->getRoles())){
            $rolesFlag['coo'] = 1;
        }
        if(in_array(\Auto\Bundle\ManagerBundle\Entity\Member::ROLE_REGION_MANAGER,$member->getRoles())){
            $rolesFlag['region_manager'] = 1;
        }
        if(in_array(\Auto\Bundle\ManagerBundle\Entity\Member::ROLE_OPERATE,$member->getRoles())){
            $rolesFlag['operate'] = 1;
        }
        if(in_array(\Auto\Bundle\ManagerBundle\Entity\Member::ROLE_MARKET,$member->getRoles())){
            $rolesFlag['market'] = 1;
        }
        if(in_array(\Auto\Bundle\ManagerBundle\Entity\Member::ROLE_FINANCE,$member->getRoles())){
            $rolesFlag['finance'] = 1;
        }
        if(in_array(\Auto\Bundle\ManagerBundle\Entity\Member::ROLE_SERVER,$member->getRoles())){
            $rolesFlag['server'] = 1;
        }
        return ['member'=>$member,'rolesflag'=>$rolesFlag];
    }

    /**
     * @Route("/edit/{id}", methods="POST", name="auto_admin_role_update")
     * @Template("AutoAdminBundle:Role:new.html.twig")
     */
    public function updateAction(Request $req, $id)
    {
        $member=$this->getDoctrine()
            -> getRepository('AutoManagerBundle:Member')
            ->find($id);
        if(empty($member)){
            return $this->render(
                "AutoAdminBundle:Default:message.html.twig",
                ['message'=>'此用户不存在!']
            );
        }

        $form = $req->request->get('form');
        if(empty($form['roles'])){
            return $this->render(
                "AutoAdminBundle:Default:message.html.twig",
                ['message'=>'请设置角色!']
            );
        }
        if ($form) {
            if($member->getName() == null){
                $member->setName($form['name']);
            }
            $roles = $form['roles'];
            $newRoles = array(\Auto\Bundle\ManagerBundle\Entity\Member::ROLE_USER);
            if(in_array('admin',$roles)){
                $newRoles[] = \Auto\Bundle\ManagerBundle\Entity\Member::ROLE_ADMIN;
            }
            if(in_array('coo',$roles)){
                $newRoles[] = \Auto\Bundle\ManagerBundle\Entity\Member::ROLE_COO;
            }
            if(in_array('region_manager',$roles)){
                $newRoles[] = \Auto\Bundle\ManagerBundle\Entity\Member::ROLE_REGION_MANAGER;
            }
            if(in_array('operate',$roles)){
                $newRoles[] = \Auto\Bundle\ManagerBundle\Entity\Member::ROLE_OPERATE;
            }
            if(in_array('market',$roles)){
                $newRoles[] = \Auto\Bundle\ManagerBundle\Entity\Member::ROLE_MARKET;
            }
            if(in_array('finance',$roles)){
                $newRoles[] = \Auto\Bundle\ManagerBundle\Entity\Member::ROLE_FINANCE;
            }
            if(in_array('server',$roles)){
                $newRoles[] = \Auto\Bundle\ManagerBundle\Entity\Member::ROLE_SERVER;
            }

            $member->setRoles($newRoles);
            $man = $this->getDoctrine()->getManager();
            $man->persist($member);
            $man->flush();
            return $this->redirect($this->generateUrl('auto_admin_role_list'));
        }
        return $this->redirect($this->generateUrl('auto_admin_role_edit',array('id'=>$member->getId())));
    }

    /**
     * @Route("/del/{id}", methods="GET", name="auto_admin_role_delete",requirements={"id"="\d+"})
     */
    public function deleteAction($id)
    {
        $member=$this->getDoctrine()
            -> getRepository('AutoManagerBundle:Member')
            ->find($id);
        if(empty($member)){
            return $this->render(
                "AutoAdminBundle:Default:message.html.twig",
                ['message'=>'用户不存在!']
            );
        }
        $operator = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Operator')
            ->findOneBy(['member'=>$member]);
        if(!empty($operator)){
            $man = $this->getDoctrine()->getManager();
            $man->remove($operator);
            $man->flush();
        }
        $region = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Region')
            ->findOneBy(['member'=>$member]);
        if(!empty($region)){
            $man = $this->getDoctrine()->getManager();
            $man->remove($region);
            $man->flush();
        }
        $man = $this->getDoctrine()->getManager();
        $member->setRoles(array(\Auto\Bundle\ManagerBundle\Entity\Member::ROLE_USER));
        $man->persist($member);
        $man->flush();

        return $this->redirect($this->generateUrl('auto_admin_role_list'));
    }





}