<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 15/10/26
 * Time: ионГ10:29
 */

namespace Auto\Bundle\OperateBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @Route("/account")
 */

class AccountController extends Controller{

    /**
     * @Route("/auth", methods="GET", name="auto_operate_account_auth")
     * @Template()
     */
    public function authAction(){
    	
        return [ ];
    }
}