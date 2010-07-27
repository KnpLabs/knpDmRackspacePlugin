<?php

require_once dirname(__FILE__) . '/../../vendor/rackspace/cloudfiles.php';

class knpDmRackspaceSyncFilesTask extends dmContextTask
{
  /**
   * @see sfTask
   */
  protected function configure()
  {
    $this->addArguments(array());

    $this->addOptions(array(
      new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', true),
      // new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'dev'),
    ));

    $this->namespace = 'rack';
    $this->name = 'files';
    $this->briefDescription = 'Sync files with Rackspace';
    $this->detailedDescription = $this->briefDescription;
  }

  /**
   * @see sfTask
   */
  protected function execute($arguments = array(), $options = array())
  {
    $synchronizer = $this->createSynchronizer();
    $this->logSection('rackspace', 'URI is ' . $synchronizer->getUri());

    $dirs = array('theme/images', 'cache');

    foreach($dirs as $dir) {
      $synchronizer->sync($dir);
    }
  }

  public function createSynchronizer()
  {
    $username = sfConfig::get('app_rackspace_username');
    $key = sfConfig::get('app_rackspace_key');
    $containerName = sfConfig::get('app_rackspace_container');
    $webDir = sfConfig::get('sf_web_dir');
    $ttl = sfConfig::get('app_rackspace_ttl');
    $mimeTypeResolver = $this->get('mime_type_resolver');
    $dispatcher = $this->get('dispatcher');

    $this->logSection("rackspace", "Connecting '$username' to '$containerName'");

    $auth = new CF_Authentication($username, $key);
    $auth->authenticate();
    $conn = new CF_Connection($auth);
    $container = $conn->create_container($containerName);
    $synchronizer = new knpDmRackspaceSynchronizer($container, $webDir, $ttl, $mimeTypeResolver, $dispatcher);

    return $synchronizer;
  }

}
