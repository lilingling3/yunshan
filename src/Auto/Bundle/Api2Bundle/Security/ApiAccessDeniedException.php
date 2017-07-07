<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 16/5/27
 * Time: 下午5:28
 */

namespace Auto\Bundle\Api2Bundle\Security;

use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;
use Symfony\Component\HttpFoundation\Request;
use Auto\Bundle\Api2Bundle\Controller\BaseController;
use Symfony\Component\HttpFoundation\JsonResponse;



class ApiAccessDeniedException implements AccessDeniedHandlerInterface{

    public function onAccessDeniedException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();
        //Get the root cause of the exception.
        while (null !== $exception->getPrevious()) {
            $exception = $exception->getPrevious();
        }
        if ($exception instanceof AccessDeniedException) {
            //Forward to third-party.
            $data = array(
                'error' => array(
                    'code' => $exception->getCode(),
                    'message' => $exception->getMessage()
                )
            );
            $response = new JsonResponse($data);
            $event->setResponse($response);






        }
    }

    public function handle(Request $request, AccessDeniedException $accessDeniedException){

    }
}