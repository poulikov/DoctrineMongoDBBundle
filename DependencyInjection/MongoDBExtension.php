<?php

namespace Bundle\DoctrineMongoDBBundle\DependencyInjection;

use Symfony\Components\DependencyInjection\Loader\LoaderExtension,
Symfony\Components\DependencyInjection\Loader\XmlFileLoader,
Symfony\Components\DependencyInjection\BuilderConfiguration,
Symfony\Components\DependencyInjection\Definition,
Symfony\Components\DependencyInjection\Reference,
Bundle\ApiBundle\Helpers\EventManager;

/*
 * This file is part of The OpenSky Project
*/

/**
 * Description of MongrineExtension
 *
 * @author Bulat Shakirzyanov <bulat@theopenskyproject.com>
 */
class MongoDBExtension extends LoaderExtension
{

  protected $_resources = array(
    'odm' => 'odm.xml',
  );

  protected $alias;
  protected $bundleDirs;
  protected $bundles;

  public function __construct(array $bundleDirs, array $bundles)
  {
    $this->bundleDirs = $bundleDirs;
    $this->bundles = $bundles;
  }

  public function odmLoad($config) {
    $configuration = new BuilderConfiguration();

    $loader = new XmlFileLoader(__DIR__.'/../Resources/config');
    $configuration->merge($loader->load($this->_resources['odm']));

    $config['default_document_manager'] = isset($config['default_document_manager']) ?
      $config['default_document_manager'] : 'default';
    foreach (array('metadata_driver', 'cache_driver') as $key)
    {
      if (isset($config[$key]))
      {
        $configuration->setParameter('doctrine.odm.'.$key, $config[$key]);
      }
    }
    $config['document_managers'] = isset($config['document_managers']) ?
      $config['document_managers'] : array($config['default_document_manager'] => array())
    ;
    foreach ($config['document_managers'] as $name => $connection)
    {
      $ormConfigDef = new Definition('Doctrine\ODM\MongoDB\Configuration');
      $configuration->setDefinition(
        sprintf('doctrine.odm.%s_configuration', $name), $ormConfigDef
      );

      $drivers = array('metadata');
      foreach ($drivers as $driver)
      {
        $definition = $configuration->getDefinition(sprintf('doctrine.odm.cache.%s', $configuration->getParameter('doctrine.odm.cache_driver')));
        $clone = clone $definition;
        $clone->addMethodCall('setNamespace', array(sprintf('doctrine_%s_', $driver)));
        $configuration->setDefinition(sprintf('doctrine.odm.%s_cache', $driver), $clone);
      }

      // configure metadata driver for each bundle based on the type of mapping files found
      $mappingDriverDef = new Definition('Doctrine\ODM\MongoDB\Mapping\Driver\DriverChain');
      $bundleEntityMappings = array();
      $bundleDirs = $this->bundleDirs;
      $aliasMap = array();
      foreach (array_reverse($this->bundles) as $className)
      {
        $tmp = dirname(str_replace('\\', '/', $className));
        $namespace = str_replace('/', '\\', dirname($tmp));
        $class = basename($tmp);

        if (!isset($bundleDirs[$namespace]))
        {
          continue;
        }

        $type = false;
        if (is_dir($dir = $bundleDirs[$namespace].'/'.$class.'/Resources/config/doctrine/metadata'))
        {
          $type = $this->detectMappingType($dir);
        }

        if (is_dir($dir = $bundleDirs[$namespace].'/'.$class.'/Documents'))
        {
          $type = 'annotation';

          $aliasMap[$class] = $namespace.'\\'.$class.'\\Documents';
        }

        if (false !== $type)
        {
          $mappingDriverDef->addMethodCall('addDriver', array(
            new Reference(sprintf('doctrine.odm.metadata_driver.%s', $type)),
            $namespace.'\\'.$class.'\\Documents'
          ));
        }
      }
      $configuration->setDefinition('doctrine.odm.metadata_driver', $mappingDriverDef);

      $methods = array(
        'setMetadataCacheImpl' => new Reference('doctrine.odm.metadata_cache'),
        'setMetadataDriverImpl' => new Reference('doctrine.odm.metadata_driver'),
        'setProxyDir' => '%kernel.cache_dir%/doctrine/Proxies',
        'setProxyNamespace' => 'Proxies',
      );

      foreach ($methods as $method => $arg)
      {
        $ormConfigDef->addMethodCall($method, array($arg));
      }
      $server = isset($connection['connection']) ?
        (isset($config['connections']) ?
          (isset($config['connections'][$connection['connection']]) ?
            (isset($config['connections'][$connection['connection']]['server']) ?
              $config['connections'][$connection['connection']]['server'] : null
            ) : null
          ) : null
        ) : null;

      if(null !== $server) {
        $ormConfigDef = new Definition('Doctrine\ODM\MongoDB\Mongo', array($server));
      } else {
        $ormConfigDef = new Definition('Doctrine\ODM\MongoDB\Mongo');
      }
      $configuration->setDefinition(
        sprintf('doctrine.odm.%s_connection', $name), $ormConfigDef
      );

      $ormEmArgs = array(
        new Reference(sprintf('doctrine.odm.%s_connection', $name)),
        new Reference(sprintf('doctrine.odm.%s_configuration', $name))
      );
      $ormEmDef = new Definition('Doctrine\ODM\MongoDB\DocumentManager', $ormEmArgs);
      $ormEmDef->setConstructor('create');

      $configuration->setDefinition(
        sprintf('doctrine.odm.%s_document_manager', $name),
        $ormEmDef
      );

      if ($name == $config['default_document_manager']) {
        $configuration->setAlias(
          'doctrine.odm.document_manager',
          sprintf('doctrine.odm.%s_document_manager', $name)
        );
      }
    }

    $configuration->setAlias(
      'doctrine.odm.cache',
      sprintf(
      'doctrine.odm.cache.%s',
      $configuration->getParameter('doctrine.odm.cache_driver')
      )
    );

    return $configuration;
  }

  /**
   * Detect the type of Doctrine 2 mapping files located in a given directory.
   * Simply finds the first file in a directory and returns the extension. If no
   * mapping files are found then the annotation type is returned.
   *
   * @param string $dir
   * @return string $type
   */
  protected function detectMappingType($dir) {
    $files = glob($dir.'/*.*');
    if (!$files) {
      return 'annotation';
    }
    $info = pathinfo($files[0]);

    return $info['extension'];
  }

  /**
   * Returns the namespace to be used for this extension (XML namespace).
   *
   * @return string The XML namespace
   */
  public function getNamespace() {
    return 'http://www.symfony-project.org/schema/dic/symfony';
  }

  /**
   * @return string
   */
  public function getXsdValidationBasePath() {
    return __DIR__ . '/../Resources/config/';
  }

  /**
   * Returns the recommended alias to use in XML.
   *
   * This alias is also the mandatory prefix to use when using YAML.
   *
   * @return string The alias
   */
  public function getAlias() {
    return 'mongodb';
  }
}
