<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 15/9/16
 * Time: 下午5:09
 */

namespace Auto\Bundle\MobileBundle\Controller;

use Auto\Bundle\ManagerBundle\Entity\Member;
use Auto\Bundle\ManagerBundle\Entity\AuthMember;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @Route("/more")
 */
class MoreController extends Controller
{
    /**
     * @Route("/list", methods="GET", name="auto_mobile_more_list")
     * @Template()
     */
    public function listAction()
    {
        return [];
    }


}