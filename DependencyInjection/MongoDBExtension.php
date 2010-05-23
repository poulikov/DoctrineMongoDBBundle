<?php

namespace Bundle\DoctrineMongoDBBundle\DependencyInjection;

use Symfony\Components\DependencyInjection\Loader\LoaderExtension,
Symfony\Components\DependencyInjection\Loader\XmlFileLoader,
Symfony\Components\DependencyInjection\BuilderConfiguration,
Symfony\Components\DependencyInjection\Definition,
Symfony\Components\DependencyInjection\Reference,
Bundle\ApiBundle\Helpers\EventManager;

/**
 * Description of MongoDBExtension
 *
 * @author Bulat Shakirzyanov <bulat@theopenskyproject.com>
 */
class MongoDBExtension extends LoaderExtension
{

    protected $_resources = array(
        'odm' => 'odm.xml',
    );

    protected $bundleDirs;
    protected $bundles;
    protected $appName;
    protected $tmpDir;

    public function __construct(array $bundleDirs, array $bundles, $appName = null, $tmpDir = null)
    {
        $this->bundleDirs = $bundleDirs;
        $this->bundles    = $bundles;
        $this->appName    = $appName;
        $this->tmpDir     = $tmpDir;
    }

    public function odmLoad($config)
    {
        $configuration = new BuilderConfiguration();

        $loader = new XmlFileLoader(__DIR__.'/../Resources/config');
        $configuration->merge($loader->load($this->_resources['odm']));

        $configuration->setParameter('doctrine.odm.mongo.default_database', $this->appName);
        $configuration->setParameter(
            'doctrine.odm.mongo.proxy_dir',
            sprintf('%s/Proxies', $this->tmpDir)
        );

        if (isset($config['cache_driver']))
        {
            $configuration->setAlias(
                'doctrine.odm.cache',
                sprintf('doctrine.odm.cache.%s', $config['cache_driver'])
            );
        }

        $config['metadata_driver'] = isset($config['metadata_driver']) ?
            $config['metadata_driver'] : 'auto';
        if ('auto' == $config['metadata_driver'])
        {
            $configuration->setAlias(
                'doctrine.odm.mongo.metadata_driver',
                'doctrine.odm.mongo.metadata_driver.chain'
            );
            $driverChainDef = $configuration->getDefinition(
                'doctrine.odm.mongo.metadata_driver.chain'
            );
            foreach (array_reverse($this->bundles) as $className)
            {
                $tmp = dirname(str_replace('\\', '/', $className));
                $namespace = str_replace('/', '\\', dirname($tmp));
                $class = basename($tmp);

                if (!isset($this->bundleDirs[$namespace]))
                {
                    continue;
                }

                $type = false;

                if (is_dir($dir = $this->bundleDirs[$namespace].'/'.$class.'/Resources/config/doctrine/metadata'))
                {
                    $type = $this->detectMappingType($dir);
                }

                if (is_dir($dir = $this->bundleDirs[$namespace].'/'.$class.'/Documents'))
                {
                    $type = 'annotation';
                }

                if (false !== $type)
                {
                    $driverChainDef->addMethodCall(
                        'addDriver',
                        array(
                            new Reference(
                                sprintf('doctrine.odm.mongo.metadata_driver.%s', $type)
                            ),
                            $namespace.'\\'.$class
                        )
                    );
                }
            }
        }
        else
        {
            $configuration->setAlias(
                'doctrine.odm.mongo.metadata_driver',
                sprintf(
                    'doctrine.odm.mongo.metadata_driver.%s',
                    $config['metadata_driver']
                )
            );
        }

        $config['default_document_manager'] = isset($config['default_document_manager']) ?
            $config['default_document_manager'] : 'default';

        $config['document_managers'] = isset($config['document_managers']) ?
            $config['document_managers'] : array($config['default_document_manager'] => array())
        ;
        $defaultManagerParams = array(
            'connection' => 'default',
        );
        $defaultConnectionParams = array(
            'server'  => sprintf('localhost:27017/%s', $this->appName),
            'options' => array('connect' => true)
        );
        $documentManagers = array();
        $connections = array();
        foreach ($config['document_managers'] as $name => $params)
        {
            $managerParams = array_merge($defaultManagerParams, $params);
            $documentManagers[$name] = $managerParams;
            $connectionName = $managerParams['connection'];
            if (isset($config['connections']) && isset($config['connections'][$connectionName]))
            {
                $conn = array_merge($defaultConnectionParams, $config['connections'][$connectionName]);
            }
            else
            {
                $conn = $defaultConnectionParams;
            }
            $connections[$connectionName] = $conn;
        }

        $defaultConfDef    = $configuration->getDefinition('doctrine.odm.mongo.configuration');
        $defaultConnection = $configuration->getDefinition('doctrine.odm.mongo.connection');
        $defaultDocManager = $configuration->getDefinition('doctrine.odm.document_manager');

        if (count($documentManagers) > 1)
        {
            foreach ($documentManagers as $name => $connection)
            {
                $server = $connections[$connection['connection']]['server'];
                $options = $connections[$connection['connection']]['options'];

                $pieces = explode('/', $server);
                $db = isset ($pieces[1]) ? $pieces[1] : null;

                $odmConfiguration = clone $defaultConfDef;
                $odmConfiguration->setClass(
                    $configuration->getParameter('doctrine.odm.mongo.configuration_class')
                );
                if (isset ($db)) {
                    $odmConfiguration->addMethodCall('setDefaultDB', array($db));
                }

                $odmConnection = clone $defaultConnection;
                $odmConnection->setClass(
                    $configuration->getParameter('doctrine.odm.mongo_class')
                );
                $odmConnection->setArguments(array($server, $options));

                $configuration->setDefinition(
                    sprintf('doctrine.odm.mongo.%s_connection', $name),
                    $odmConnection
                );

                $configuration->setDefinition(
                    sprintf('doctrine.odm.mongo.%s_configuration', $name),
                    $odmConfiguration
                );

                $documentManager = clone $defaultDocManager;
                $documentManager->setClass(
                    $configuration->getParameter('doctrine.odm.mongo.document_manager_class')
                );
                $odmManagerArgs = array(
                    new Reference(sprintf('doctrine.odm.mongo.%s_connection', $name)),
                    new Reference(sprintf('doctrine.odm.mongo.%s_configuration', $name))
                );
                $documentManager->setArguments($odmManagerArgs);

                $configuration->setDefinition(
                    sprintf('doctrine.odm.%s_document_manager', $name),
                    $documentManager
                );

                if ($name == $config['default_document_manager']) {
                    $configuration->setAlias(
                        'doctrine.odm.document_manager',
                        sprintf('doctrine.odm.%s_document_manager', $name)
                    );
                }
            }
        }
        else
        {
            list($name, $connection) = each($documentManagers);
            $server  = $connections[$connection['connection']]['server'];
            $options = $connections[$connection['connection']]['options'];

            $pieces = explode('/', $server);
            $db = isset ($pieces[1]) ? $pieces[1] : null;

            $configuration->setParameter('doctrine.odm.mongo.default_database', $db);
            $configuration->setParameter('doctrine.odm.mongo.default_server', $server);
            $configuration->setParameter(
                'doctrine.odm.mongo.default_connection_options', $options
            );
            $configuration->setAlias(
                sprintf('doctrine.odm.%s_document_manager', $name),
                'doctrine.odm.document_manager'
            );
        }

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
