<?php

namespace Bundle\MongrineBundle;

use Symfony\Foundation\Bundle\Bundle as BaseBundle,
Symfony\Components\DependencyInjection\ContainerInterface,
Symfony\Components\DependencyInjection\Loader\Loader,
Bundle\MongrineBundle\DependencyInjection\MongrineExtension;

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
    Loader::registerExtension(new MongrineExtension(
      $container->getParameter('kernel.bundle_dirs'),
      $container->getParameter('kernel.bundles')
    ));

    $metadataDirs = array();
    $entityDirs = array();
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
        if (is_dir($dir = $bundleDirs[$namespace].'/'.$class.'/Entities'))
        {
          $entityDirs[] = realpath($dir);
        }
      }
    }
    $container->setParameter('doctrine.odm.metadata_driver.mapping_dirs', $metadataDirs);
    $container->setParameter('doctrine.odm.entity_dirs', $entityDirs);
  }
}
