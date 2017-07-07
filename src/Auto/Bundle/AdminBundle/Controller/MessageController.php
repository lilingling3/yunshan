<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 15/7/20
 * Time: 下午2:54
 */

namespace Auto\Bundle\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Auto\Bundle\ManagerBundle\Entity\Message;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Auto\Bundle\ManagerBundle\Form\MessageType;

/**
 * @Route("/message")
 */
class MessageController extends Controller {

    const PER_PAGE = 20;

    /**
     * @Route("/list/{page}", methods="GET", name="auto_admin_message_list",
     * requirements={"page"="\d+"},
     * defaults={"page"=1})
     * @Template()
     */
    public function listAction($page = 1)
    {
        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:Message')
                ->createQueryBuilder('c')
        ;
        $messages =
            new Paginator(
                $qb
                    ->select('c')
                    ->orderBy('c.id', 'DESC')
                    ->setMaxResults(self::PER_PAGE)
                    ->setFirstResult(self::PER_PAGE * ($page - 1))
            );

        $total = ceil(count($messages) / self::PER_PAGE);

        return ['messages'=>$messages,'page'=>$page,'total'=>$total];
    }

    /**
     * @Route("/new", methods="GET", name="auto_admin_message_new")
     * @Template()
     */
    public function newAction()
    {

        $form = $this->createFormBuilder()
            ->add('message', new MessageType())
            ->add('mobile', 'text')
            ->setAction($this->generateUrl('auto_admin_message_create'))
            ->setMethod("POST")
            ->getForm();

        return ['form'  => $form->createView()];
    }

    /**
     * @Route("/new", methods="POST", name="auto_admin_message_create")
     * @Template("AutoAdminBundle:Message:new.html.twig")
     */
    public function createAction(Request $req)
    {

        $message = new \Auto\Bundle\ManagerBundle\Entity\Message();

        $form = $this->createFormBuilder()
            ->add('message', new MessageType(),['data'=>$message])
            ->add('mobile', 'text')
            ->setAction($this->generateUrl('auto_admin_message_create'))
            ->setMethod("POST")
            ->getForm();

        $form->handleRequest($req);

        if ($form->isValid()) {

            $data = $form->getData();

            $message = $data['message'];

            $mobile = $data['mobile'];
            $member = $this->getDoctrine()
                ->getRepository('AutoManagerBundle:Member')
                ->findOneBy(['mobile'=>$mobile]);

            if(empty($member)){

                return $this->render(
                    "AutoAdminBundle:Default:message.html.twig",
                    ['message'=>'没有该用户!']
                );

            }

            $message
                ->setMember($member)
                ->setStatus(\Auto\Bundle\ManagerBundle\Entity\Message::MESSAGE_UNREAD)
            ;

            $man = $this->getDoctrine()->getManager();
            $man->persist($message);
            $man->flush();

            return $this->redirect($this->generateUrl('auto_admin_message_list'));
        }
        return ['form'  => $form->createView()];
    }


    /**
     * @Route("/del/{id}", methods="GET", name="auto_admin_message_delete",requirements={"id"="\d+"})
     */
    public function deleteAction($id)
    {
        $man = $this->getDoctrine()->getManager();
        $message = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Message')
            ->find($id);
        $man->remove($message);
        $man->flush();

        return $this->redirect($this->generateUrl('auto_admin_message_list'));
    }
}