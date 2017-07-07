<?php

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;

class AppKernel extends Kernel
{

    public function init()
    {
        date_default_timezone_set( 'Asia/Shanghai' );
        parent::init();
    }


    public function registerBundles()
    {
        $bundles = array(
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new Symfony\Bundle\TwigBundle\TwigBundle(),
            new Symfony\Bundle\MonologBundle\MonologBundle(),
            new Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle(),
            new Symfony\Bundle\AsseticBundle\AsseticBundle(),
            new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),
            new Auto\Bundle\ManagerBundle\AutoManagerBundle(),
            new Auto\Bundle\WebBundle\AutoWebBundle(),
            new Auto\Bundle\AdminBundle\AutoAdminBundle(),
            new Mojomaja\Bundle\PhotographBundle\MojomajaPhotographBundle(),
            new Auto\Bundle\ApiBundle\AutoApiBundle(),
            new Snc\RedisBundle\SncRedisBundle(),
            new Auto\Bundle\MobileBundle\AutoMobileBundle(),
            new Auto\Bundle\OperateBundle\AutoOperateBundle(),
            new Auto\Bundle\WapBundle\AutoWapBundle(),
            new Auto\Bundle\Api2Bundle\AutoApi2Bundle(),
            new Liuggio\ExcelBundle\LiuggioExcelBundle(),
        );

        if (in_array($this->getEnvironment(), array('dev', 'test'))) {
            $bundles[] = new Symfony\Bundle\DebugBundle\DebugBundle();
            $bundles[] = new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
            $bundles[] = new Sensio\Bundle\DistributionBundle\SensioDistributionBundle();
            $bundles[] = new Sensio\Bundle\GeneratorBundle\SensioGeneratorBundle();
        }

        return $bundles;
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load($this->getRootDir().'/config/config_'.$this->getEnvironment().'.yml');
    }
}
