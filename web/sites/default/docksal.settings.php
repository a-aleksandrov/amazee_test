<?php

/**
 * @file
 * Lando local development override configuration feature.
 */

// Docksal DB settings.
if (getenv('DOCKSAL') === "ON") {
  $databases['default']['default'] = [
    'driver' => 'mysql',
    'database' => 'default',
    'username' => "root",
    'password' => "root",
    'host' => 'db',
    'port' => 3306,
  ];
  
  $dir = dirname(DRUPAL_ROOT);

  // Use development service parameters.
  $settings['container_yamls'][] = $dir . '/docroot/sites/development.services.yml';

  // Allow access to update.php.
  $settings['update_free_access'] = TRUE;


  /**
   * Show all error messages, with backtrace information.
   *
   * In case the error level could not be fetched from the database, as for
   * example the database connection failed, we rely only on this value.
   */
  $config['system.logging']['error_level'] = 'verbose';

  /**
   * Disable CSS and JS aggregation.
   */
  $config['system.performance']['css']['preprocess'] = FALSE;
  $config['system.performance']['js']['preprocess'] = FALSE;

  /**
   * Enable access to rebuild.php.
   *
   * This setting can be enabled to allow Drupal's php and database cached
   * storage to be cleared via the rebuild.php page. Access to this page can also
   * be gained by generating a query string from rebuild_token_calculator.sh and
   * using these parameters in a request to rebuild.php.
   */
  $settings['rebuild_access'] = FALSE;

  /**
   * Skip file system permissions hardening.
   *
   * The system module will periodically check the permissions of your site's
   * site directory to ensure that it is not writable by the website user. For
   * sites that are managed with a version control system, this can cause problems
   * when files in that directory such as settings.php are updated, because the
   * user pulling in the changes won't have permissions to modify files in the
   * directory.
   */
  $settings['skip_permissions_hardening'] = TRUE;

  /**
   * Temporary file path:
   *
   * A local file system path where temporary files will be stored. This
   * directory should not be accessible over the web.
   *
   * Note: Caches need to be cleared when this value is changed.
   *
   * See https://www.drupal.org/node/1928898 for more information
   * about global configuration override.
   */
  $config['system.file']['path']['temporary'] = '/tmp';

  /**
   * Private file path.
   */
  $settings['file_private_path'] = $dir . '/files-private';
  if (isset($acsf_site_name)) {
    $settings['file_public_path'] = "sites/default/files/$acsf_site_name";
    $settings['file_private_path'] = "$repo_root/files-private/$acsf_site_name";
  }

  /**
   * Trusted host configuration.
   *
   * See full description in default.settings.php.
   */
  $settings['trusted_host_patterns'] = ['.*'];

  $settings['hash_salt'] = '1f22d547c23f9c15bdd327ea3deec32e';
}
