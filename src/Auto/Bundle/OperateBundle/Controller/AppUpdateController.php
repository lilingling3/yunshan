<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 15/9/18
 * Time: 下午5:58
 */

namespace Auto\Bundle\OperateBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Exception\RuntimeException;
use Doctrine\ORM\Tools\Pagination\Paginator;
/**
 * @Route("/appupdate")
 */

class AppUpdateController extends Controller{

    /**
     * @Route("/add", methods="GET", name="auto_operate_app_update_add")
     * @Template()
     */
    public function addAction(){
        
        return [];
    }

    /**
     * @Route("/addappdata", methods="POST", name="auto_operate_app_update_addappdata")
     * @Template("AutoOperateBundle:AppUpdate:add.html.twig")
     */
    public function adddappdataAction(Request $req){

        $versionCode=$req->request->get('versionCode');
        $version=$req->request->get('version');
        $forceUpgrade=$req->request->get('forceUpgrade');
        $explain=$req->request->get('explainjson');
        $downloadsite = isset($_FILES['downloadsite'])?$_FILES['downloadsite']["tmp_name"]:'';


        $appname='android'.date('Ymdhis').".apk";
        $app_path =$this->container->getParameter('download_app_path');
        $file = $app_path.'/'.$appname;
       if(!move_uploaded_file($downloadsite,$file)){
            return $this->render(
                "AutoOperateBundle:Default:message.html.twig",
                ['message' => '上传文件失败！']);
        };
        $app = new \Auto\Bundle\ManagerBundle\Entity\AppUpdate();
        $app->setVersionCode($versionCode);
        $app->setVersion($version);
        $app->setForceUpgrade($forceUpgrade);
        $app->setExplain($explain);
        $app->setDownloadsite($appname);
        $man = $this->getDoctrine()->getManager();
        $man->persist($app);
        $man->flush();
        return $this->redirect($this->generateUrl('auto_operate_app_update_add'));
    }
    /**
     * @Route("/list", methods="GET", name="auto_operate_app_update_list")
     * @Template()
     */
    public function listAction(){

        $baseUrl = $this->get('auto_manager.curl_helper')->base_url();
        $post_json = $this->get('auto_manager.curl_helper')->do_post($baseUrl.$this->generateUrl
            ('api2_upgrade_checkapp'),
            ['VersionCode'=>123,'platform'=>2]);
        $data = json_decode($post_json,true);
        dump($data);exit;
        if($data['errorCode']!=0){
            return $this->render(
                "AutoWapBundle:Default:message.html.twig",
                ['message' => $data['errorMessage']]);
        }


        return [];
    }

}