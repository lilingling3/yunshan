<?php
/**
 * Created by PhpStorm.
 * User: dongzhen
 * Date: 16/9/19
 * Time: 下午5:34
 */

namespace Auto\Bundle\Api2Bundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/upgrade")
 */
class UpgradeController extends BaseController
{
    //当前版本号
    const VERSION_CODE = 131001;
    //当前版本号
    const IOS_VERSION_CODE = 122000;

    //是否强制升级 0:不强制  1:强制
    const FORCE_UPGRADE = 1;

    const VERSION  = '1.3.1';


    const IOS_VERSION = '1.2.2';

    //安装包url

    const PACKAGE_URL = 'https://go.win-sky.com.cn/package/andriod/drivevi_131.apk';


    /**
     * @Route("/check", methods="POST")
     */
    public function checkAction(Request $req)
    {
        $version_code = $code = $req->request->getInt('VersionCode');
        $platform = $req->request->getInt('platform');
        $upgradeReason='1.新增车辆问题上传入口，用户用车前如发现车辆存在问题可拍照上传，有机会获得优惠券。'."\n". '2.添加车辆使用手册，不再为陌生车型困扰，安全快捷出行。'."\n". '驾呗祝您出行愉快！';
        $upgradeReasonIos='修复了一些bug '."\n";
        if($platform == 2 && $version_code >=self::VERSION_CODE ){


            return new JsonResponse([
                'errorCode'    => '0',
                'data' =>  [
                    'canUpgrade'  => '0',
                    'devVersionCode' =>self::VERSION_CODE,
                    'forceUpgrade' =>self::FORCE_UPGRADE,
                    'upgradeReason' =>$upgradeReason,
                    'packageUrl'  =>self::PACKAGE_URL,
                    'version' => self::VERSION
                ],
            ]);
        }
        else if($platform == 1 && $version_code >=self::IOS_VERSION_CODE) {
            return new JsonResponse([
                'errorCode'    => '0',
                'data' =>  [
                    'canUpgrade'  => '0',
                    'devVersionCode' =>self::VERSION_CODE,
                    'forceUpgrade' =>0,
                    'upgradeReason' =>$upgradeReasonIos,
                    'packageUrl'  =>self::PACKAGE_URL,
                    'version' => self::VERSION
                ],
            ]);
        }

        else if($platform == 1 && $version_code <self::IOS_VERSION_CODE) {

            return new JsonResponse([
                'errorCode'    => '0',
                'data' =>  [
                    'canUpgrade'  => '0',
                    'devVersionCode' =>self::VERSION_CODE,
                    'forceUpgrade' =>self::FORCE_UPGRADE,
                    'upgradeReason' => $upgradeReasonIos,
                    'packageUrl'  =>self::PACKAGE_URL,
                    'version' => self::VERSION
                ],
            ]);
        }
        else {
            return new JsonResponse([
                'errorCode'    => '0',
                'data' =>  [
                    'canUpgrade'  => '1',
                    'devVersionCode' =>self::VERSION_CODE,
                    'forceUpgrade' =>self::FORCE_UPGRADE,
                    'upgradeReason' =>$upgradeReason,
                    'packageUrl'  =>self::PACKAGE_URL,
                    'version' => self::VERSION
                ],
            ]);
        }
    }



    /**
     * @Route("/checkapp", methods="POST",name="api2_upgrade_checkapp")
     */
    public function checkappAction(Request $req)
    {
        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:AppUpdate')
                ->createQueryBuilder('a')
        ;
        $app = $qb
            ->select('a')
            ->orderBy('a.id', 'DESC')
            ->getQuery()
            ->setMaxResults(1)
            ->getOneorNullResult();

        if(empty($app)){
            return new JsonResponse([
                "errorCode"=>'1',
                "errorMessage"=>"没有查找到数据"
            ]);
        }
        $upgradeReasons=json_decode($app->getExplain(),true);
        $upgradeReason=null;
        $key=1;
        foreach( $upgradeReasons as $value ){
            if(empty($upgradeReason)){
                $upgradeReason=$key.'、'.$value.'.';
            }
            else{
                $upgradeReason=$upgradeReason.'\n'.$key.'、'.$value.'.';
            }
            $key++;
        }

        $VERSION_CODE_STR=$app->getVersionCode();
        $VERSION_STR=$app->getVersion();
        $FORCE_UPGRADE_STR=$app->getForceUpgrade();
        $version_code = $code = $req->request->getInt('VersionCode');

        $platform = $req->request->getInt('platform');
        $baseUrl = $this->get('auto_manager.curl_helper')->base_url();
        $app_path =$this->container->getParameter('download_app_path');
        $downloadsite=$app->getDownloadsite();
        $PACKAGE_URL_STR=$baseUrl.'/'.$app_path.'/'.$downloadsite;
        if($platform == 2 && $version_code >=$VERSION_CODE_STR ){


            return new JsonResponse([
                'errorCode'    => '0',

                'data' =>  [

                    'canUpgrade'  => '0',

                    'devVersionCode' =>$VERSION_CODE_STR,

                    'forceUpgrade' =>$FORCE_UPGRADE_STR,

                    'upgradeReason' =>$upgradeReason,

                    'packageUrl'  =>$PACKAGE_URL_STR,

                    'version' => $VERSION_STR


                ],
            ]);


        }else{



            return new JsonResponse([
                'errorCode'    => '0',

                'data' =>  [

                    'canUpgrade'  => '1',

                    'devVersionCode' =>$VERSION_CODE_STR,

                    'forceUpgrade' =>$FORCE_UPGRADE_STR,

                    'upgradeReason' => $upgradeReason,

                    'packageUrl'  =>$PACKAGE_URL_STR,

                    'version' => $VERSION_STR
                ],
            ]);
        }
    }

}
