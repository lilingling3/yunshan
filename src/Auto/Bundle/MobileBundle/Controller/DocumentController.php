<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 15/9/16
 * Time: ����5:09
 */

namespace Auto\Bundle\MobileBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @Route("/document")
 */
class DocumentController extends Controller{

     /**
     * @Route("/about", methods="GET", name="auto_mobile_document_about")
     * @Template()
     */
    public function aboutAction(){
    					
        return [];   
    }
    	
    	
   /**
     * @Route("/protocol", methods="GET", name="auto_mobile_document_protocol")
     * @Template()
     */
    public function protocolAction(){
    					
        return [];   
    }

    /**
     * @Route("/charge", methods="GET", name="auto_mobile_document_charge")
     * @Template()
     */
    public function chargeAction(){

        return [];
    }
    /**
     * @Route("/fe", methods="GET", name="auto_mobile_document_fe")
     * @Template()
     */
    public function feAction(){

        return [];
    }
    /**
     * @Route("/test", methods="GET", name="auto_mobile_document_test")
     * @Template()
     */
    public function testAction(){

        return [];
    }



    /**
     * @Route("/inviteFriends", methods="GET", name="auto_mobile_document_inviteFriends")
     * @Template()
     */
    public function inviteFriendsAction(){

        return [];
    }

}