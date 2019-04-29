# Amazee test task. 
This is a standard drupal instalation. I added Docksal to the project, so you can use it.
## Settings and using.
Clone the project locally. 
> git clone git@github.com:a-aleksandrov/amazee_test.git amazee_test

Cd into project dir. 
> cd amazee_test

Init docksal.
> fin project start

Run composer intstall
> fin composer install

Import database.
 > fin db import ./amazee_test.sql --progress
 
Just for a case clear cache.
 > fin drush cr
 
 Log in as admin:
  > fin drush uli
  
 And login to the site. Base URL should be http://amazee-test.docksal
 You can test the functionality within Article CT. field_calculator with Calculator formatter is already added and configured. You can use Testing modules to run Unit test of the module. 
 
 If you use another web-development environment you should do the same things but via your environment specific commands. 