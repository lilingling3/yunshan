<?php

namespace Auto\Bundle\ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\Tools\Pagination\Paginator;


/**
 * @Route("/fast")
 */
class FastDFSController extends BaseController
{
    const PER_PAGE = 20;

    const INVITE_REWARD_CASH = 5;

    /**
     * 上传文件
     *
     * @Route("/upload", methods="POST", name="auto_api_fast_upload")
     */
    public function uploadAction(Request $req)
    {

        $path = '/data/www/data/123.jpg';

        $fileName = $this->get('auto_manager.fastdfs_helper')->uploadFile($path);

        return new JsonResponse([
            'errorCode' => self::E_OK,
            'filePath'  => $fileName
        ]);
    }

    /**
     * 获取文件列表
     *
     * @Route("/list", methods="POST", name="auto_api_fast_list")
     */
    public function listAction(Request $req)
    {

        // 列出文件
        $result = $this->get('auto_manager.fastdfs_helper')->list();


        return new JsonResponse([
            'errorCode' => self::E_OK,
            'list'      => $result
        ]);
    }

    /**
     * 多图上传
     *
     * @Route("/multiUpload", methods="POST", name="auto_api_fast_multi_upload")
     */
    public function multiUploadAction(Request $req)
    {
        return new JsonResponse([]);
    }

    /**
     * 删除图片
     *
     * @Route("/delete", methods="POST", name="auto_api_fast_delete")
     */
    public function deleteAction(Request $req)
    {
        $fileName = $req->request->get('delteFileName');

        // 删除文件
        $result = $this->get('auto_manager.fastdfs_helper')->delete(trim($fileName));


        return new JsonResponse([
            'errorCode' => self::E_OK,
            'filePath'  => $result
        ]);
    }

    /**
     * DEBUG 删除所有文件
     *
     * @Route("/deleteAll", methods="POST", name="auto_api_fast_delete_all")
     */
    public function deleteAllAction(Request $req)
    {
        return new JsonResponse([]);
    }

    /**
     * 文件详情
     *
     * @Route("/detail", methods="POST", name="auto_api_fast_detail")
     */
    public function detailAction(Request $req)
    {
        return new JsonResponse([]);
    }

}
