<?php

namespace Auto\Bundle\Api2Bundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @Route("/")
 */
class DefaultController extends BaseController
{
    /**
     * @Route("/index", methods="POST",name="auto_api_2_index")
     */
    public function indexAction()
    {
        var_dump($this->getUser());exit;
    }

    /**
     * @Route("/", methods="GET",name="auto_api_2_index1")
     */
    public function testAction()
    {
        echo 111;exit;

    }
}
