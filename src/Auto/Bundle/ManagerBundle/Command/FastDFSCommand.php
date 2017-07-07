<?php
/**
 * Created by PhpStorm.
 * User: dongzhen
 * Date: 16/10/13
 * Time: 下午2:14
 */
namespace Auto\Bundle\ManagerBundle\Command;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class FastDFSCommand extends ContainerAwareCommand{

    public function configure()
    {
        $this
            ->setName('auto:fastdfs:upload')
            ->setDescription('test fastdfs upload file ')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {


        // 确认目标文件夹大小 、 文件个数
        // 传输耗时 平均耗时


        $file_src_dir = "/data/www/data/00";


        $data = [];
        $res = $this->searchDir($file_src_dir, $data);

        $res['count'] = count($res);
//        $res['totalSize'] = $res['total'];

//        var_dump($res);

        $list = [];
        foreach ($res as $key => $val)
        {

            echo "Transfer ". $val['path'] ."start ". microtime() . PHP_EOL;
            $list[] = $this->getContainer()->get('auto_manager.fastdfs_helper')->uploadFile($val['path']);
            echo "Transfer ". $val['path'] ."start ". microtime() . PHP_EOL;
            echo "file size :". $val['size'] . PHP_EOL;

        }


        var_dump($list);
    }

    private function searchDir($path, &$data)
    {
        if(is_dir($path))
        {
            $dp = dir($path);

            while ($file = $dp->read())
            {
                if ($file != '.' && $file != '..')
                {
                    $this->searchDir($path . "/" .$file, $data);
                }
            }
        }

        if(is_file($path)){
            $data[] = ['path' => $path, 'size' => filesize($path)];
        }

        return $data;
    }


}