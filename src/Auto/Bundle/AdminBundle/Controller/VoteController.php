<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 15/7/20
 * Time: 下午2:54
 */

namespace Auto\Bundle\AdminBundle\Controller;

use Auto\Bundle\ManagerBundle\Entity\VoteOptions;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Auto\Bundle\ManagerBundle\Entity\Vote;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Auto\Bundle\ManagerBundle\Form\VoteType;
use Auto\Bundle\ManagerBundle\Form\VoteOptionsType;

/**
 * @Route("/vote")
 */
class VoteController extends Controller {

    const PER_PAGE = 20;


    /**
     * @Route("/list/{page}", methods="GET", name="auto_admin_vote_list",
     * requirements={"page"="\d+"},
     * defaults={"page"=1})
     * @Template()
     */
    public function listAction($page = 1)
    {
        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:Vote')
                ->createQueryBuilder('v')
        ;
        $votes =
            new Paginator(
                $qb
                    ->select('v')
                    ->orderBy('v.id', 'DESC')
                    ->setMaxResults(self::PER_PAGE)
                    ->setFirstResult(self::PER_PAGE * ($page - 1))
            );

        $total = ceil(count($votes) / self::PER_PAGE);
        return ['votes'=>$votes,'page'=>$page,'total'=>$total];
    }


    /**
     * @Route("/new/option/{id}", methods="GET", name="auto_admin_vote_option_new" ,
     * requirements={"id"="\d+"})
     * @Template()
     */
    public function newOptionAction($id)
    {

        $vote = $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:Vote')
                ->find($id);
        $vote_option = new VoteOptions();
        $vote_option->setVote($vote);
        $form = $this->createForm(new VoteOptionsType(), $vote_option, [
            'action' => $this->generateUrl('auto_admin_vote_option_create',['id'=>$id]),
            'method' => 'POST'
        ]);

        $options = $this
            ->getDoctrine()
            ->getRepository('AutoManagerBundle:VoteOptions')
            ->findBy(['vote'=>$vote]);

        return ['form'  => $form->createView(),'options'=>$options];
    }

    /**
     * @Route("/new/option/{id}", methods="POST", name="auto_admin_vote_option_create",
     * requirements={"id"="\d+"})
     * @Template("AutoAdminBundle:Vote:newOption.html.twig")
     */
    public function createOptionAction($id,Request $req)
    {

        $vote = $this
            ->getDoctrine()
            ->getRepository('AutoManagerBundle:Vote')
            ->find($id);
        $vote_option = new VoteOptions();
        $vote_option->setVote($vote);
        $form = $this->createForm(new VoteOptionsType(), $vote_option, [
            'action' => $this->generateUrl('auto_admin_vote_option_create',['id'=>$id]),
            'method' => 'POST'
        ]);
        $form->handleRequest($req);
        if ($form->isValid()) {
            $man = $this->getDoctrine()->getManager();
            $man->persist($vote_option);
            $man->flush();
            return $this->redirect($this->generateUrl('auto_admin_vote_list'));
        }
        return ['form'  => $form->createView()];
    }



    /**
     * @Route("/new", methods="GET", name="auto_admin_vote_new" )
     * @Template()
     */
    public function newAction()
    {

        $vote = new Vote();

        $form = $this->createForm(new VoteType(), $vote, [
            'action' => $this->generateUrl('auto_admin_vote_create'),
            'method' => 'POST'
        ]);

        return ['form'  => $form->createView()];
    }

    /**
     * @Route("/new", methods="POST", name="auto_admin_vote_create")
     * @Template("AutoAdminBundle:Vote:new.html.twig")
     */
    public function createAction(Request $req)
    {
        $vote = new Vote();

        $form = $this->createForm(new VoteType(), $vote, [
            'action' => $this->generateUrl('auto_admin_vote_create'),
            'method' => 'POST'
        ]);

        $form->handleRequest($req);

        if ($form->isValid()) {
            $man = $this->getDoctrine()->getManager();
            $man->persist($vote);
            $man->flush();
            return $this->redirect($this->generateUrl('auto_admin_vote_list'));
        }
        return ['form'  => $form->createView()];
    }

    /**
     * @Route("/del/option/{id}", methods="GET", name="auto_admin_vote_option_delete",requirements={"id"="\d+"})
     */
    public function deleteOptionAction($id)
    {
        $man = $this->getDoctrine()->getManager();
        $option = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:VoteOption')
            ->find($id);
        $man->remove($option);
        $man->flush();

        return $this->redirect($this->generateUrl('auto_admin_vote_list'));
    }



}