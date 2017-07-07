<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 15/7/20
 * Time: 上午11:39
 */

namespace Auto\Bundle\ManagerBundle\Command;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class AddImageCommand extends ContainerAwareCommand{

    public function configure()
    {
        $this
            ->setName('auto:tinify:image')
            ->setDescription('add color')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        ini_set("memory_limit","-1");


        $directory="/home/xuhu/www/day/";

        $mydir = dir($directory);


        $files = [];

        while($file = $mydir->read()){

            if((is_dir("$directory/$file")) AND ($file!=".") AND ($file!=".."))

            {
                tree("$directory/$file");
            }else{


                if(strstr($file,'jpg')&&substr($file,0,4)==2015){

                    $files[] = $directory.$file;
                }


            }
        }
        $mydir->close();

        var_dump($files);

        exit;








        $man = $this->getContainer()->get('doctrine')->getManager();
        $members =
            $man
            ->getRepository('AutoManagerBundle:Member')
            ->findAll()
        ;

        array_map(function ($member) use($man){
            echo $member->getId().PHP_EOL;

            if(strlen($member->getPortrait())>60){

                echo $name=$this->getTinifyImage(base64_decode($member->getPortrait()));
                $member->setPortrait($name);
                $man->persist($member);
                $man->flush();
            }
        },$members);


        $authMembers =
            $man
                ->getRepository('AutoManagerBundle:AuthMember')
                ->findAll()
        ;


        array_map(function ($auth) use($man){
            echo $auth->getMember()->getId().PHP_EOL;

            if(strlen($auth->getPersonimage())>60){

                echo $name=$this->getTinifyImage(base64_decode($auth->getPersonimage()));
                $auth->setPersonimage($name);
                $man->persist($auth);
                $man->flush();
            }

            if(strlen($auth->getLicenseImage())>60){

                echo $name=$this->getTinifyImage(base64_decode($auth->getLicenseImage()));
                $auth->setLicenseImage($name);
                $man->persist($auth);
                $man->flush();
            }


        },$authMembers);

    }



    public function uploadImage(){

        $redis = $this->getContainer()->get('snc_redis.default');

        list($key,$tiniy_count)= $this->getKeyAndCount($redis);

        \Tinify\setKey($key);

     //   \Tinify\fromBuffer($binary_code)->toBuffer();

    }


    public function getTinifyImage($binary_code){

        $redis = $this->getContainer()->get('snc_redis.default');

        list($key,$tiniy_count)= $this->getKeyAndCount($redis);

        \Tinify\setKey($key);

        $binary = $resultData = \Tinify\fromBuffer($binary_code)->toBuffer();

        $helper = $this->getContainer()->get('mojomaja_photograph.helper.photograph');

        $filename = tempnam(sys_get_temp_dir(), "lecar");
        file_put_contents($filename, $binary);

        $name = "";
        $tmp = tempnam(null, null);
        if (copy($filename, $tmp)) {
            chmod($tmp, 0644);
            $name = $helper->persist($tmp, true);
        }

        unlink($tmp);
        unlink($filename);

        $redis_cmd= $redis->createCommand('set',array('tinify-'.$key,$tiniy_count+1));
        $redis->executeCommand($redis_cmd);
        return $name;
}



    public function getKeyAndCount($redis){

        $keys = ['EFd2Ggqbf9omeXSLdHPmsc7ER7FcV714',
                'LmGvldLHkc62i1SaKVmreIyz-jOnxnJB',
                'V6WyZzZmkbSRGGQP-bKN6K8cXjDdMEOU',
                'LSQOrIChmPgw8U_3YHtVBXZBwNdC7kUg',
                'KPcMtPPWe2sjua7G-FVnEj5F4tImlpJB',
                'FbhG-amSta6N2BSRpu2Z8NMKpC85aD4Z',
                'Ke2Jb_SoJstT659JDwFMdSpy71ah75fS',
                'tkXd6kJ9atdGI1OWfU-nimRIf7we5iOR',
                '-DX9Jq0bgPysiQ7wlISO2wa1FTcwLTjH',
                'nDlDoJSPvWVq1qGR2EZsVVG9sL1M_CNO',
                'bku_0F_IfMDVPbTK3iJQ-2zQ1FazzATL',
                '_na4Q1c1uyjTwQUfdGGwtGZOTP3WzQkO'
            ];

        $key = $keys[array_rand($keys,1)];

        $redis_cmd= $redis->createCommand('get',array('tinify-'.$key));
        $tiniy_count = intval($redis->executeCommand($redis_cmd));

        if($tiniy_count<=450){
            return [$key,$tiniy_count];
        }else{
            $this->getKey($redis);
        }

    }

}