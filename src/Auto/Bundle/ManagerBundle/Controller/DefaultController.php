<?php

namespace Auto\Bundle\ManagerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('AutoManagerBundle:Default:index.html.twig', array('name' => $name));

    }
}
