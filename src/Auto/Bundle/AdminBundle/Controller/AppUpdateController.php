<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 15/9/18
 * Time: 下午5:58
 */

namespace Auto\Bundle\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Exception\RuntimeException;

/**
 * @Route("/appupdate")
 */

class AppUpdateController extends Controller{
    /**
     * @Route("/show", methods="GET", name="auto_operate_app_update_show")
     * @Template()
     */
    public function showAction(){
        return [];
    }
}