# Setup#
get John Wage's ODM library for Doctrine 2,
get Symfony 2

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
