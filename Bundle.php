<?php

namespace Bundle\DoctrineMongoDBBundle;

use Symfony\Foundation\Bundle\Bundle as BaseBundle,
Symfony\Components\DependencyInjection\ContainerInterface,
Symfony\Components\DependencyInjection\Loader\Loader,
Bundle\DoctrineMongoDBBundle\DependencyInjection\MongoDBExtension;

/**
 * Description of Bundle
 *
 * @author Bulat Shakirzyanov <bulat@theopenskyproject.com>
 */
class Bundle extends BaseBundle 
{
    public function buildContainer(ContainerInterface $container) 
    {
        $kernelBundleDirs = $container->getParameter('kernel.bundle_dirs');
        $kernelBundles    = $container->getParameter('kernel.bundles');
        $appName          = $container->getParameter('kernel.name');
        $cacheDir         = $container->getParameter('kernel.cache_dir');

        Loader::registerExtension(
            new MongoDBExtension($kernelBundleDirs, $kernelBundles, $appName, $cacheDir)
        );

        $metadataDirs = array();
        $documentDirs = array();
        $bundleDirs   = $kernelBundleDirs;
        foreach ($kernelBundles as $className)
        {
            $tmp       = dirname(str_replace('\\', '/', $className));
            $namespace = str_replace('/', '\\', dirname($tmp));
            $class     = basename($tmp);

            if (isset($bundleDirs[$namespace]))
            {
                if (is_dir($dir = $bundleDirs[$namespace].'/'.$class.'/Resources/config/doctrine/metadata'))
                {
                    $metadataDirs[] = realpath($dir);
                }
                if (is_dir($dir = $bundleDirs[$namespace].'/'.$class))
                {
                    $documentDirs[] = realpath($dir);
                }
            }
        }
        $container->setParameter('doctrine.odm.metadata_driver.mapping_dirs', $metadataDirs);
        $container->setParameter('doctrine.odm.document_dirs', $documentDirs);
    }
}
