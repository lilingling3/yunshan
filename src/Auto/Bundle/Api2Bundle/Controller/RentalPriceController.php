<?php
/**
 * Created by PhpStorm.
 * User: dongzhen
 * Date: 16/9/19
 * Time: 下午2:17
 */
namespace Auto\Bundle\Api2Bundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\Tools\Pagination\Paginator;


/**
 * @Route("/rentalPrice")
 */
class RentalPriceController extends BaseController
{

    /**
     * @Route("/introduction", methods="GET")
     * @Template()
     */
    public function introductionAction()
    {

       return [];
    }




}
