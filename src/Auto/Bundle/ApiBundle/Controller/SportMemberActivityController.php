<?php
/**
 * Created by PhpStorm.
 * User: dongzhen
 * Date: 16/10/13
 * Time: 上午12:05
 */
namespace Auto\Bundle\ApiBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\HttpFoundation\Request;
use Auto\Bundle\ManagerBundle\Entity\Message;

/**
 * @Route("/sportMember")
 */
class SportMemberActivityController extends BaseController {

    /**
     * @Route("/getSportsMember", methods="POST")
     */
    public function getSportsMember(Request $req){

        $mobile = $req->request->get('mobile');

        $redis = $this->container->get('snc_redis.default');

        //获取当前体育会员可领取的个数
        $code_count_cmd = $redis->createCommand('get',array('sport_member_code_count'));

        $code_count = $redis->executeCommand($code_count_cmd);

        $code_count_num = json_decode($code_count,true);

        //取出会员code数据
        $redis_cmd= $redis->createCommand('lindex',array("sport_member_code",$code_count_num));

        $sports_data = $redis->executeCommand($redis_cmd);

        $sports_data_arr = json_decode($sports_data,true);

        $code_count_num = $code_count_num-1;

        if($code_count_num<-1){

            return new JsonResponse([
                'errorCode'    =>  3336,
                'errorMessage' => '兑换码已经领完了！'
            ]);

        }

        if(time()>1477756800){

            return new JsonResponse([
                'errorCode'    =>  3333,
                'errorMessage' => '活动结束了！'
            ]);

        }


        $redis_cmd_llen = $redis->createCommand('llen',array("sport_member_mobile"));

        $llen = $redis->executeCommand($redis_cmd_llen);


        for($i = 0;$i<$llen;$i++){

            $redis_cmd= $redis->createCommand('lindex',array("sport_member_mobile",$i));

            $mobile_data = $redis->executeCommand($redis_cmd);

            $reids_mobile = json_decode( $mobile_data,true);

            if($mobile == $reids_mobile ){

                return new JsonResponse([
                    'errorCode'    =>  2222,
                    'errorMessage' => '您已领取'
                ]);
            }
        }


        $count_cmd = $redis->createCommand('set',array('sport_member_code_count',json_encode($code_count_num)));

        $redis->executeCommand($count_cmd);

        $client = $this->get('auto_manager.sms_helper');

        $client->send(

            $mobile,
            $this->renderView(
                'AutoManagerBundle:Coupon:sport.sms.twig',
                ['code' => $sports_data_arr['code']]
            )
        );

        $redis_cmd= $redis->createCommand('lpush',array("sport_member_mobile",json_encode($mobile)));

        $redis->executeCommand($redis_cmd);

        //删除已经被领取的code
        $redis_cmd_del = $redis->createCommand('lrem', array("sport_member_code", 1, $sports_data));
        $redis->executeCommand($redis_cmd_del);

        return new JsonResponse([
            'errorCode'    =>  self::E_OK,
            'errorMessage' => '领取成功，稍后请查收短信'
        ]);


    }



}