<?php
/**
 * Created by PhpStorm.
 * User: luyao
 * Date: 17/5/12
 * Time: 上午10:45
 */
namespace Auto\Bundle\ManagerBundle\Helper;

/**
 * 管理文件服务器
 *
 * Class FastDFSHelper
 * @package Auto\Bundle\ManagerBundle\Helper
 */
class FastDFSHelper extends AbstractHelper{

    private $tracker;
    private $storage;

    /**
     * 
     */
    public function connect()
    {
        // 获取连接Tracker配置
        $tracker = fastdfs_tracker_get_connection();

        if (!fastdfs_active_test($tracker))
        {
            error_log('active test ERROR:'. fastdfs_get_last_error_no() . ", ERROR Info:" . fastdfs_get_last_error_info());
            return 1;
        }

        // 连接Storage
        if (false == $storage = fastdfs_tracker_query_storage_store())
        {
            error_log('query storage ERROR:'. fastdfs_get_last_error_no() . ", ERROR Info:" . fastdfs_get_last_error_info());
            return 2;
        }

        // 连接Tracker
        if (false == $server = fastdfs_connect_server($storage['ip_addr'], $storage['port']))
        {
            error_log('connect ERROR:'. fastdfs_get_last_error_no() . ", ERROR Info:" . fastdfs_get_last_error_info());
            return 3;
        }


        if (!fastdfs_active_test($server))
        {
            error_log("conn storage ERROR:" . fastdfs_get_last_error_no() . ", error info: " . fastdfs_get_last_error_info());
            return 4;
        }

        $this->setTracker($tracker);
        $this->setStorage($storage);
    }
    
    
    /**
     * 上传文件方法
     *
     * @param $fileName  文件全路径
     * @param $metaList  文件扩展信息
     * @param $extName   文件扩展名
     *
     * @return  文件服务器上文件路径
     */
    public function uploadFile($fileName, $metaList=array(), $extName=null) {

        $this->connect();

        $fileName = trim($fileName);

        // 验证文件是否存在
        if (!file_exists($fileName)) {
            return false;
        }

        return fastdfs_storage_upload_by_filename1($fileName, $extName, $metaList, null, $this->getTracker(), $this->getStorage());
    }

    /**
     * 删除文件
     *
     * @param $fileName 文件名
     *
     * @return boolean True / False
     */
    public function delete($fileName)
    {
        $this->connect();

        $fileName = trim($fileName);

        $res = ['fileName' => $fileName];

        // 文件是否存在
        if ($fileName && fastdfs_storage_file_exist1($fileName, $this->getTracker(), $this->getStorage()))
        {
            $res['res'] = fastdfs_storage_delete_file1($fileName);
        }
        else
        {
            $res['res'] = '111';
        }

        return $res;
    }

    /**
     * 文件是否存在
     *
     * @param $fileName
     *
     * @return boolean TRUE / FALSE
     */
    public function isFileExists($fileName)
    {
        $this->connect();

        return $fileName ? fastdfs_storage_file_exist1($fileName, $this->getTracker(), $this->getStorage()): false;
    }

    /**
     * DEBUG 删除全部文件
     *
     *
     *
     * @return boolean TRUE / FALSE
     */
    public function deleteall()
    {
        
    }

    /**
     * 获得分布式服务器信息
     *
     * 例如：版本信息，group 存储信息 ，上传文件个数
     * 上传成功、失败次数，可用空间，上传字节数，下载字节数等
     *
     *
     * @param
     *
     * @return mixed
     *
     */
    public function getServerInfo()
    {
        // 获取全部group 信息
        $grpList = fastdfs_tracker_list_groups();

        if ($grpList) {


        }

        return [
            'ver' => fastdfs_client_version(),

        ];
    }


    private function setTracker($tracker)
    {
        $this->tracker = $tracker;
    }

    private function getTracker()
    {
        return $this->tracker;
    }

    private function setStorage($storage)
    {
        $this->storage = $storage;
    }

    private function getStorage()
    {
        return $this->storage;
    }


}