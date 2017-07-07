<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 16/6/3
 * Time: 下午5:01
 */

namespace Auto\Bundle\ApiBundle\EventListener;

use Auto\Bundle\ApiBundle\Controller\PartnerController;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PartnerControllerListener
{
    private $manager;
    protected $container;

    public function __construct(ObjectManager $manager, ContainerInterface $container)
    {
        $this->manager = $manager;
        $this->container = $container;
    }

    public function onKernelController(FilterControllerEvent $event)
    {
        $controller = $event->getController();
        $controller = $controller[0];
        $new = new \ReflectionObject($controller);

        $controller_array = [
            'Auto\Bundle\ApiBundle\Controller\PartnerController',
        ];

        if (in_array($new->getName(), $controller_array)) {
            $post_data = $token = $event->getRequest()->request->getIterator();

            $is_post = true;
            foreach ($post_data as $key => $val) {
                $post_array[$key] = $val;
            }

            if (empty($post_array)) {
                $is_post = false;
                $get_data = $token = $event->getRequest()->query->getIterator();
                foreach ($get_data as $key => $val) {
                    $post_array[$key] = $val;
                }
            }

            $this->log($event->getRequest()->getUri(), $post_array, $is_post);

            $check = $this->checkSign($post_array);

            if ($check['error'] == 1) {
                throw new UnauthorizedHttpException('This action needs a valid token!');
            } else {
                if ($is_post) {
                    $event->getRequest()->request->add(['partnerID' => $check['partner_id']]);
                } else {
                    $event->getRequest()->query->add(['partnerID' => $check['partner_id']]);
                }
            }
        }
    }

    /**
     * 记录partner 请求
     * @param $url
     * @param $data
     * @param bool $is_post
     */
    private function log($url, $data, $is_post = true)
    {
        if (!PartnerController::LOG) {
            return;
        }
        $logs = '[' . date('y/m/d H:i:s') . ']: ';
        $is_post = $is_post ? 'POST' : 'GET';
        $logs .= $url . ' -- ' . $is_post . ' Data: ' . json_encode($data) . "\n";
        file_put_contents('/data/logs/partnerConnect.log', $logs, FILE_APPEND);
    }


    /**
     * 验证请求是否合法
     *
     * @param $post_data
     * @return array
     */
    public function checkSign($post_data)
    {
        if (isset($post_data['sign']) && isset($post_data['timestamp']) && isset($post_data['appkey'])) {
            if (abs((new \DateTime())->getTimestamp() - $post_data['timestamp']) > 120) {
                return ['error' => 1, 'message' => '请检查请求时间'];
            }

            $secret = null;
            $redis = $this->container->get('snc_redis.default');
            $partner_key_cache_key = 'partner_key_';
            $partner_id_cache_key = 'partner_id_';
            $redis_cmd = $redis->createCommand('GET', array($partner_key_cache_key . $post_data['appkey']));
            $secret = $redis->executeCommand($redis_cmd);
            $redis_cmd = $redis->createCommand('GET', array($partner_id_cache_key . $post_data['appkey']));
            $partner_id = $redis->executeCommand($redis_cmd);

            if ($secret === null || $partner_id === null) {
                $partner = $this->manager
                    ->getRepository('AutoManagerBundle:Partner')
                    ->findOneBy(['code' => $post_data['appkey'], 'status' => 1]);

                if (empty($partner)) {
                    return ['error' => 1, 'message' => '没有该合作公司'];
                } else {
                    $secret = $partner->getSecret();
                    $redis_cmd = $redis->createCommand('SET', array($partner_key_cache_key . $partner->getCode(), $partner->getSecret()));
                    $redis->executeCommand($redis_cmd);
                    $partner_id = $partner->getId();
                    $redis_cmd = $redis->createCommand('SET', array($partner_id_cache_key . $partner->getCode(), $partner->getId()));
                    $redis->executeCommand($redis_cmd);
                }
            }

            $sign = $post_data['sign'];
            unset($post_data['sign']);

            if (strcasecmp($this->getSign($post_data, $secret), $sign) == 0) {
                return ['error' => 0, 'message' => '验证通过', 'partner_id' => $partner_id];
            }
        }
        return ['error' => 1, 'message' => '验证失败'];
    }

    /**
     * 获得签名
     */
    public function getSign($dataArr, $key)
    {

        ksort($dataArr);

        $queryStr = '';

        foreach ($dataArr as $k => $v) {
            $queryStr .= $k . '=' . $v . '&';
        }

        $resultStr = $queryStr . $key;

        // 拼接签名串
        $signature = urlencode($resultStr);

        // md5加密
        $signValue = md5($signature);

        return $signValue;
    }
}

