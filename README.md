# Setup#
Get [MongoDB ODM here](http://github.com/doctrine/mongodb-odm)

# Usage #
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

After you have DocumentManager instance, you can use it:

    $user = $dm->find('Documents\User', array('name' => 'Bulat S.'));

Full MongoDB ODM documentation [is available at doctrine website](http://www.doctrine-project.org/projects/mongodb_odm/1.0/docs/en)

Happy Coding!