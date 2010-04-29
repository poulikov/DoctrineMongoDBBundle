# Usage #
Add the following to your yaml configuration file:

	mongrine.odm:
	  default_entity_manager: default
	  cache_driver:            array
	  entity_managers:
		default:
		  connection:      mongodb
	  connections:
		mongodb:
		  server:        mongo://localhost/somedatabase
