<?php

namespace Bundle\DoctrineMongoDBBundle;

use Symfony\Foundation\Bundle\Bundle as BaseBundle,
Symfony\Components\DependencyInjection\ContainerInterface,
Symfony\Components\DependencyInjection\Loader\Loader,
Bundle\DoctrineMongoDBBundle\DependencyInjection\MongoDBExtension;

/* 
 * This file is part of The OpenSky Project
*/

/**
 * Description of Bundle
 *
 * @author Bulat Shakirzyanov <bulat@theopenskyproject.com>
 */
class Bundle extends BaseBundle
{
  public function buildContainer(ContainerInterface $container)
  {
    Loader::registerExtension(new MongoDBExtension(
      $container->getParameter('kernel.bundle_dirs'),
      $container->getParameter('kernel.bundles')
    ));

    $metadataDirs = array();
    $documentDirs = array();
    $bundleDirs = $container->getParameter('kernel.bundle_dirs');
    foreach ($container->getParameter('kernel.bundles') as $className)
    {
      $tmp = dirname(str_replace('\\', '/', $className));
      $namespace = str_replace('/', '\\', dirname($tmp));
      $class = basename($tmp);

      if (isset($bundleDirs[$namespace]))
      {
        if (is_dir($dir = $bundleDirs[$namespace].'/'.$class.'/Resources/config/doctrine/metadata'))
        {
          $metadataDirs[] = realpath($dir);
        }
        if (is_dir($dir = $bundleDirs[$namespace].'/'.$class.'/Documents'))
        {
          $documentDirs[] = realpath($dir);
        }
      }
    }
    $container->setParameter('doctrine.odm.metadata_driver.mapping_dirs', $metadataDirs);
    $container->setParameter('doctrine.odm.document_dirs', $documentDirs);
  }
}
