<?php

namespace Bundle\DoctrineMongoDBBundle\Tests;

require_once __DIR__ . '/bootstrap.php';

use Symfony\Components\DependencyInjection\Builder;
use Symfony\Components\DependencyInjection\Loader\Loader;

use Bundle\DoctrineMongoDBBundle\Bundle;

class DoctrineMongoDBBundleTest extends \PHPUnit_Framework_TestCase
{
    protected $defaultConfiguration = array(
        'default_document_manager' => 'array',
        'cache_driver'             => 'array',
        'metadata_driver'          => 'auto',
        'document_managers' => array(
            'default' => array(
                'connection' => 'default',
            )
        ),
        'connections' => array(
            'default' => array(
                'server'  => 'localhost/testing',
                'options' => array('connect' => true)
            )
        )
    );

    protected function createContainer($kernelParams = array())
    {
        $container = new Builder(array_merge(array(
            'kernel.root_dir'    => __DIR__,
            'kernel.environment' => 'test',
            'kernel.debug'       => true,
            'kernel.name'        => 'DoctrineMongoDBBunlde',
            'kernel.cache_dir'   => __DIR__,
            'kernel.logs_dir'    => null,
            'kernel.bundle_dirs' => array(),
            'kernel.bundles'     => array(),
            'kernel.charset'     => 'UTF-8',
        ), $kernelParams));

        return $container;
    }

    public function testCacheDriver()
    {
        $container = $this->createContainer();
        $bundle = new Bundle();
        $bundle->buildContainer($container);

        $mongoExtension = Loader::getExtension('mongodb');

        $config = $this->defaultConfiguration;

        $config['cache_driver'] = 'array';
        $configuration = $mongoExtension->odmLoad($config);
        $container->merge($configuration);

        $cacheDriver = $container->getDoctrine_ODM_CacheService();
        $this->assertTrue($cacheDriver instanceof \Doctrine\Common\Cache\ArrayCache);

        $config['cache_driver'] = 'apc';
        $configuration = $mongoExtension->odmLoad($config);
        $container->merge($configuration);

        $cacheDriver = $container->getDoctrine_ODM_CacheService();
        $this->assertTrue($cacheDriver instanceof \Doctrine\Common\Cache\ApcCache);

        $config['cache_driver'] = 'xcache';
        $configuration = $mongoExtension->odmLoad($config);
        $container->merge($configuration);

        $cacheDriver = $container->getDoctrine_ODM_CacheService();
        $this->assertTrue($cacheDriver instanceof \Doctrine\Common\Cache\XcacheCache);

        if (class_exists('Memcache'))
        {
            $config['cache_driver'] = 'memcache';
            $configuration = $mongoExtension->odmLoad($config);
            $container->merge($configuration);

            $cacheDriver = $container->getDoctrine_ODM_CacheService();
            $this->assertTrue($cacheDriver instanceof \Doctrine\Common\Cache\MemcacheCache);
        }
        else
        {
            $this->markTestSkipped('Memcache extension is not available.');
        }
    }

    public function testMetaDriver()
    {
        $container = $this->createContainer();
        $bundle = new Bundle();
        $bundle->buildContainer($container);

        $mongoExtension = Loader::getExtension('mongodb');

        $config = $this->defaultConfiguration;
        $config['metadata_driver'] = 'xml';
        $configuration = $mongoExtension->odmLoad($config);
        $container->merge($configuration);

        $metadataDriver = $container->getDoctrine_ODM_Mongo_MetadataDriverService();

        $this->assertTrue($metadataDriver instanceof \Doctrine\ODM\MongoDB\Mapping\Driver\XmlDriver);

        $config['metadata_driver'] = 'yml';
        $configuration = $mongoExtension->odmLoad($config);
        $container->merge($configuration);

        $metadataDriver = $container->getDoctrine_ODM_Mongo_MetadataDriverService();
        $this->assertTrue($metadataDriver instanceof \Doctrine\ODM\MongoDB\Mapping\Driver\YamlDriver);

        $config['metadata_driver'] = 'annotation';
        $configuration = $mongoExtension->odmLoad($config);
        $container->merge($configuration);

        $metadataDriver = $container->getDoctrine_ODM_Mongo_MetadataDriverService();
        $this->assertTrue($metadataDriver instanceof \Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver);

        $config['metadata_driver'] = 'auto';
        $configuration = $mongoExtension->odmLoad($config);
        $container->merge($configuration);
        $configuration->setParameter('doctrine.odm.cache.memcache.instance_class', '\Bundle\DoctrineMongoDBBundle\Tests\MemcacheMock');

        $metadataDriver = $container->getDoctrine_ODM_Mongo_MetadataDriverService();
        $this->assertTrue($metadataDriver instanceof \Doctrine\ODM\MongoDB\Mapping\Driver\DriverChain);
    }

    public function testEmptyConfig()
    {
        $container = $this->createContainer();
        $bundle = new Bundle();
        $bundle->buildContainer($container);

        $mongoExtension = Loader::getExtension('mongodb');

        $configuration = $mongoExtension->odmLoad(array());
        $container->merge($configuration);

        $dm = $container->getDoctrine_ODM_DocumentManagerService();

        $this->assertTrue($dm instanceof \Doctrine\ODM\MongoDB\DocumentManager);
    }

    public function testAliasForDefaultDocumentManager()
    {
        $container = $this->createContainer();
        $bundle = new Bundle();
        $bundle->buildContainer($container);

        $mongoExtension = Loader::getExtension('mongodb');

        $configuration = $mongoExtension->odmLoad($this->defaultConfiguration);
        $container->merge($configuration);

        $dmDefault = $container->getDoctrine_ODM_DocumentManagerService();
        $dmNamed = $container->getDoctrine_ODM_DefaultDocumentManagerService();

        $this->assertTrue($dmDefault === $dmNamed);
    }

    public function testUseMulitpleDocumentManagers()
    {
        $container = $this->createContainer();
        $bundle = new Bundle();
        $bundle->buildContainer($container);

        $mongoExtension = Loader::getExtension('mongodb');

        $config = $this->defaultConfiguration;
        $config['document_managers']['test'] = array(
            'connection' => 'test'
        );
        $config['connections']['test'] = array(
            'server' => 'localhost/testing',
        );
        $configuration = $mongoExtension->odmLoad($config);
        $container->merge($configuration);

        $dmDefault = $container->getDoctrine_ODM_DefaultDocumentManagerService();
        $dmTest = $container->getDoctrine_ODM_TestDocumentManagerService();

        $this->assertTrue($dmDefault !== $dmTest);
    }
}
