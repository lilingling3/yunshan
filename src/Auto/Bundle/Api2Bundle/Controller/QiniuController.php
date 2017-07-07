<?php

namespace Auto\Bundle\Api2Bundle\Controller;

use Auto\Bundle\ManagerBundle\Entity\AuthMember;
use Auto\Bundle\ManagerBundle\Entity\MobileDevice;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Security\Core\Util\SecureRandom;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

use Qiniu\Auth;
use Qiniu\Storage\UploadManager;
use Qiniu\Storage\BucketManager;

/**
 * @Route("/qiniu")
 */
class QiniuController extends BaseController
{
    private $accessKey = 'WFW3CrEH2qBSDxlqHYTQRu5-PM1KrfPijePEiiOu';
    private $secretKey = 'AXF5zE2N3WysUP0dA0cJFA6WfnrY_4Q6fU_8UPgJ';

    /**
     * @Route("/getuploadtoken", methods="POST")
     */
    public function getUploadTokenAction(Request $req)
    {
        $bucket_id = $req->request->get('bucket_id');

        $arr = $this->get('auto_manager.global_helper')->car_problem();

        if(!$bucket_id){
            return new JsonResponse([
                'errorCode'    =>  self::E_NO_BUCKET_TRANSMIT,
                'code' => self::M_NO_BUCKET_TRANSMIT
            ]);
        }
        if(1 == $bucket_id){
            $bucket = 'carproblem';
        }elseif(2 == $bucket_id){
            $bucket = 'yunshan';
        }elseif(3 == $bucket_id){
            $bucket = 'jiabei';
        }

        $redis = $this->container->get('snc_redis.default');
        $redis_cmd= $redis->createCommand('GET',array("qiniu_uptoken_$bucket"));
        if($uptoken = $redis->executeCommand($redis_cmd)){
            return new JsonResponse([
                'errorCode'    =>  self::E_OK,
                'code'         => '',
                'uptoken'      =>  $uptoken,
                'problem'      => $arr,
                'redis'        => 'in redis'
            ]);
        }

        $auth = new Auth($this->accessKey, $this->secretKey);
        // 上传文件到七牛后， 七牛将文件名和文件大小回调给业务服务器&location=$(x:location)
        $policy = array(
            "callbackUrl" => "https://gotest.win-sky.com.cn/api/account/qiniuCallback",
            "callbackBody" => "filename=$(fname)&bucket=$(bucket)&imageLabel=$(x:imageLabel)&question=$(x:question)"
        );

        $uptoken = $auth->uploadToken($bucket, null, 3600, $policy);

        $redis_cmd = $redis->createCommand('set',array("qiniu_uptoken_$bucket",$uptoken));
        $redis->executeCommand($redis_cmd);
        $redis_cmd= $redis->createCommand('EXPIRE',array("qiniu_uptoken_$bucket",3600));
        $redis->executeCommand($redis_cmd);
        return new JsonResponse([
            'errorCode'    =>  self::E_OK,
            'code'         => '',
            'uptoken'      =>  $uptoken,
            'problem'      => $arr,
            'new'          => 'new'
        ]);
    }
    /**
     * @Route("/upload/{filename}", methods="GET")
     */
    public function uploadAction($filename)
    {

        /*$auth = new Auth($this->accessKey, $this->secretKey);
        //$bucket = 'jiabei';
        // 上传文件到七牛后， 七牛将文件名和文件大小回调给业务服务器
        $policy = array(
            'callbackUrl' => 'https://gotest.win-sky.com.cn/api/account/getQiniu',
            'callbackBody' => 'filename=$(fname)&filesize=$(fsize)&bucketName=$(bucket)'
        );
        $uptoken = $auth->uploadToken($bucket, null, 3600, $policy);*/
        $uptoken = 'WFW3CrEH2qBSDxlqHYTQRu5-PM1KrfPijePEiiOu:7rRZV_hmwAWkhiMPl9Bfxe-wa9k=:eyJjYWxsYmFja1VybCI6Imh0dHBzOlwvXC9nb3Rlc3Qud2luLXNreS5jb20uY25cL2FwaVwvYWNjb3VudFwvcWluaXVDYWxsYmFjayIsImNhbGxiYWNrQm9keSI6ImZpbGVuYW1lPSQoZm5hbWUpJmJ1Y2tldD0kKGJ1Y2tldCkmaW1hZ2VMYWJlbD0kKHg6aW1hZ2VMYWJlbCkiLCJzY29wZSI6ImNhcnByb2JsZW0iLCJkZWFkbGluZSI6MTQ5MzcyNjA5NSwidXBIb3N0cyI6WyJodHRwOlwvXC91cC16Mi5xaW5pdS5jb20iLCJodHRwOlwvXC91cGxvYWQtejIucWluaXUuY29tIiwiLUggdXAtejIucWluaXUuY29tIGh0dHA6XC9cLzE4My42MC4yMTQuMTk4Il19';

        //上传文件的本地路径
        $filePath = '/data/www/yunshan/web/photograph/02/57/025780052b34cce13e95ed205cf812d8c98c49a7.jpeg';
        // 上传到七牛后保存的文件名
        $uploadMgr = new UploadManager();
        $params = array(
            'x:imageLabel' => '1314'
        );
        list($ret, $err) = $uploadMgr->putFile($uptoken, $filename, $filePath,$params);
        echo "\n====> putFile result: \n";
        if ($err !== null) {
            var_dump($err);
        } else {
            var_dump($ret);
        }
        return new JsonResponse([
            'errorCode'    =>  self::E_OK,
            'code' => ''
        ]);

    }
    /**
     * @Route("/downloadqiniu/{bucket}/{imageLabel}", methods="GET")
     */
    public function DownloadQiniuAction($bucket,$imageLabel)
    {
        $dataArr = $this->getMessageQiniu($bucket,$imageLabel);
        if(false === $dataArr){
            return new JsonResponse([
                'errorCode'    =>  self::E_NO_DATA_IN_DATABASE,
                'code' => self::M_NO_DATA_IN_DATABASE
            ]);
        }
        $bucket = $dataArr['bucket'];
        $filename = $dataArr['filename'];
        if('jiabei' == $bucket){
            $bucket_url = 'http://op1oebdxb.bkt.clouddn.com';
        }elseif('yunshan' == $bucket){
            $bucket_url = 'http://op1n9pi85.bkt.clouddn.com';
        }elseif('carproblem' == $bucket){
            $bucket_url = 'http://op1y4gyle.bkt.clouddn.com';
        }else{
            return new JsonResponse([
                'errorCode'    =>  self::E_NO_THIS_BUCKET,
                'code' => self::M_NO_THIS_BUCKET
            ]);
        }
        $auth = new Auth($this->accessKey, $this->secretKey);
        $baseUrl = $bucket_url.'/'.$filename;
        $authUrl = $auth->privateDownloadUrl($baseUrl);
        echo $authUrl;die;
    }
    /**
     * @Route("/del/{bucket}/{imageLabel}", methods="GET")
     */
    public function delAction($bucket,$imageLabel)
    {
        $dataArr = $this->getMessageQiniu($bucket,$imageLabel);
        if(false === $dataArr){
            return new JsonResponse([
                'errorCode'    =>  1,
                'code' => 'No data in the database'
            ]);
        }
        $bucket = $dataArr['bucket'];
        $filename = $dataArr['filename'];
        $auth = new Auth($this->accessKey, $this->secretKey);
        //初始化BucketManager
        $bucketMgr = new BucketManager($auth);
        //你要测试的空间， 并且这个key在你空间中存在
        //删除$bucket 中的文件 $key
        $err = $bucketMgr->delete($bucket, $filename);
        echo "\n====> delete $key : \n";
        if ($err !== null) {
            var_dump($err);
        } else {
            echo "Success!";
        }
        return new JsonResponse([
            'errorCode'    =>  self::E_OK,
            'code' => ''
        ]);
    }
    public function getMessageQiniu($bucket,$imageLabel){
        $dataArr = array();
        //$imageLabel = $imageLabel;
        $redis = $this->container->get('snc_redis.default');
        $redis_cmd= $redis->createCommand('GET',array('qiniu_'.$bucket.'_'.$imageLabel));
        $filename = $redis->executeCommand($redis_cmd);

        $dataArr['bucket'] = $bucket;
        $dataArr['filename'] = $filename;

        if(!$bucket || !$filename){
            $qb =
                $this
                    ->getDoctrine()
                    ->getRepository('AutoManagerBundle:QiniuImage')
                    ->createQueryBuilder('q');
            $QiniuImage = $qb
                ->select('q')
                ->andwhere($qb->expr()->eq('q.imageLabel', ':imageLabel'))
                ->andwhere($qb->expr()->eq('q.bucket', ':bucket'))
                ->setParameter('imageLabel', $imageLabel)
                ->setParameter('bucket', $bucket)
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();
            if(empty($QiniuImage)){
                return false;
            }
            $bucket = $QiniuImage->getBucket();
            $filename = $QiniuImage->getFilename();

            $redis = $this->container->get('snc_redis.default');
            $redis_cmd = $redis->createCommand('set',array('qiniu_'.$bucket.'_'.$imageLabel,$filename));
            $redis->executeCommand($redis_cmd);
            $redis_cmd= $redis->createCommand('EXPIRE',array('qiniu_'.$bucket.'_'.$imageLabel,3600));
            $redis->executeCommand($redis_cmd);

            $dataArr['bucket'] = $bucket;
            $dataArr['filename'] = $filename;
        }
        //var_dump($dataArr);die;
        return $dataArr;
    }


}