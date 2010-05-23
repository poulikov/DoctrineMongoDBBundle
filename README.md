# Setup#
Get [MongoDB ODM here](http://github.com/doctrine/mongodb-odm)

# Usage #

Include the bundle class in your Kernel:

	use Symfony\Foundation\Kernel;
	use Symfony\Components\DependencyInjection\Loader\YamlFileLoader as ContainerLoader;
	use Symfony\Components\Routing\Loader\YamlFileLoader as RoutingLoader;

	class YourKernelClass extends Kernel
	{
		//...
		public function registerBundles()
		{
			return array(
				new Symfony\Foundation\Bundle\KernelBundle(),
				new Symfony\Framework\WebBundle\Bundle(),
				new Symfony\Framework\ProfilerBundle\Bundle(),
				new Symfony\Framework\ZendBundle\Bundle(),
				new Symfony\Framework\SwiftmailerBundle\Bundle(),
				new Symfony\Framework\DoctrineBundle\Bundle(),
				//...
				new Bundle\DoctrineMongoDBBundle\Bundle(),
			);
		}
		//...
	}

Add the following to your yaml configuration file:

    mongodb.odm:
      default_document_manager: default
      cache_driver:            array
      document_managers:
        default:
          connection:      mongodb
      connections:
        mongodb:
          server:        localhost/somedatabase

Now in your controller do:

    $dm = $this->container->getService('doctrine.odm.document_manager');
    //or...
    $dm = $this->container->getService('doctrine.odm.default_document_manager');

You could specify multiple document managers:

    mongodb.odm:
      default_document_manager: default
      cache_driver:            array
      document_managers:
        default:
          connection:      mongodb
        additional:
          connection:      other_mongo
      connections:
        mongodb:
          server:        localhost/somedatabase
        other_mogno:
          server:        192.168.0.18/other_database

Now you can get either document manager:

    $defaultDm = $this->container->getService('doctrine.odm.default_document_manager');
    $additionalDm = $this->container->getService('doctrine.odm.additional_document_manager');

Or you could just use default connection params and one document manager:

    mongodb.odm: ~

After you have DocumentManager instance, you can use it:

    $user = $dm->find('Documents\User', array('name' => 'Bulat S.'));

Full MongoDB ODM [documentation is available at doctrine website](http://www.doctrine-project.org/projects/mongodb_odm/1.0/docs/en)

Happy Coding!