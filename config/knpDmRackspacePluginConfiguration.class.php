<?php

class knpDmRackspacePluginConfiguration extends sfPluginConfiguration
{
  protected $container;

  public function configure()
  {
    $this->dispatcher->connect('dm.context.loaded', array($this,'listenToDmContextLoaded'));
  }

  public function listenToDmContextLoaded(sfEvent $e)
  {
    if($this->configuration instanceof dmFrontApplicationConfiguration)
    {
      if(sfConfig::get('app_rackspace_enabled'))
      {
        $this->dispatcher->connect('dm.layout.filter_stylesheets', array($this, 'listenToFilterAssetsEvent'));
        $this->dispatcher->connect('dm.layout.filter_javascripts', array($this, 'listenToFilterAssetsEvent'));
      }
      $this->container = $e->getSubject()->getServiceContainer();
      $this->dispatcher->connect('dm.asset_compressor.create_cache', array($this, 'listenToAssetCompressorCreateCacheEvent'));
    }
  }

  public function listenToFilterAssetsEvent(sfEvent $event, array $assets)
  {
    $transformedAssets = array();
    foreach($assets as $asset => $options)
    {
      if(0 === strncmp($asset, '/cache/', 7))
      {
        $asset = sfConfig::get('app_rackspace_url').$asset;
      }
      $transformedAssets[$asset] = $options;
    }

    return $transformedAssets;
  }

  public function listenToAssetCompressorCreateCacheEvent(sfEvent $event)
  {
    $file = $event['file'];
    $synchronizer = $this->createSynchronizer();
    $relFile = $synchronizer->getRelativePath($file);
    $synchronizer->syncFile($relFile);
  }

  protected function createSynchronizer()
  {
    require_once dirname(__FILE__) . '/../vendor/rackspace/cloudfiles.php';
    $username = sfConfig::get('app_rackspace_username');
    $key = sfConfig::get('app_rackspace_key');
    $containerName = sfConfig::get('app_rackspace_container');
    $webDir = sfConfig::get('sf_web_dir');
    $ttl = sfConfig::get('app_rackspace_ttl');
    $mimeTypeResolver = $this->container->getService('mime_type_resolver');
    $dispatcher = $this->container->getService('dispatcher');

    $auth = new CF_Authentication($username, $key);
    $auth->authenticate();
    $conn = new CF_Connection($auth);
    $container = $conn->create_container($containerName);
    $synchronizer = new knpDmRackspaceSynchronizer($container, $webDir, $ttl, $mimeTypeResolver, $dispatcher);

    return $synchronizer;
  }
}
