<?php

class knpDmRackspaceSynchronizer
{
  /**
   * The rackspace container
   *
   * @var CF_Container
   */
  protected $container = null;

  /**
   * The assets root dir
   *
   * @var string
   */
  protected $webDir = null;

  /**
   * the CDN enabled Container's URI
   *
   * @var string
   */
  protected $uri = null;

  /**
   * Event dispatcher mainly used to log messages
   *
   * @var sfEventDispatcher
   */
  protected $dispatcher = null;

  public function __construct(CF_Container $container, $webDir, $ttl, dmMimeTypeResolver $mimeTypeResolver, sfEventDispatcher $dispatcher = null)
  {
    $this->container = $container;
    $this->webDir = $webDir;
    $this->uri = $container->make_public($ttl);
    $this->mimeTypeResolver = $mimeTypeResolver;
    $this->dispatcher = $dispatcher;
  }

  public function sync($relDir)
  {
    if('/' === $relDir{0}) {
      throw new InvalidArgumentException($relDir.' is not a relative web dir');
    }
    $dir = dmOs::join($this->webDir, $relDir);
    if(!is_dir($dir)) {
      throw new InvalidArgumentException($relDir.' does not exist in '.$this->webDir);
    }
    $this->syncDir($dir);
  }

  protected function syncDir($dir)
  {
    $this->log('/'.$this->getRelativePath($dir));
    $files = sfFinder::type('file')->maxDepth(0)->name('*')->in($dir);
    foreach($files as $file) {
      $this->syncFile($file);
    }

    $dirs = sfFinder::type('directory')->maxDepth(0)->name('*')->in($dir);
    foreach($dirs as $dir) {
      $relativePath = $this->getRelativePath($dir);
      $this->container->create_paths($relativePath . '/dummy');
      $this->syncDir($dir);
    }
  }

  public function getRelativePath($path)
  {
    return str_replace($this->webDir.'/', '', $path);
  }

  public function syncFile($file)
  {
    $extension = pathinfo($file, PATHINFO_EXTENSION);
    if('php' === $extension) {
      return;
    }
    // local hash
    $md5Local = md5_file($file);

    // remote hash
    $relativePath = $this->getRelativePath($file);
    try {
      $obj = $this->container->get_object($relativePath);

      if($obj->getETag() != $md5Local) {
        $this->log('~ '.$relativePath);
        $obj->content_type = $this->getMimeTypeFromExtension($extension);
        $obj->load_from_filename($file);
      } 
    } catch(NoSuchObjectException $e) {
      $this->log('+ '.$relativePath);
      $obj = $this->container->create_object($relativePath);
      $obj->content_type = $this->getMimeTypeFromExtension($extension);
      $obj->load_from_filename($file);
    }
  }

  protected function getMimeTypeFromExtension($extension)
  {
    return $this->mimeTypeResolver->getByExtension($extension);
  }

  protected function log($message)
  {
    if($this->dispatcher) {
      $this->dispatcher->notify(new sfEvent($this, 'command.log', array($message)));
    }
  }

  /**
   * Get container
   * @return CF_Container
   */
  public function getContainer()
  {
    return $this->container;
  }

  /**
   * Set container
   * @param  CF_Container
   * @return null
   */
  public function setContainer($container)
  {
    $this->container = $container;
  }

  /**
   * Get webDir
   * @return string
   */
  public function getWebDir()
  {
    return $this->webDir;
  }

  /**
   * Set webDir
   * @param  string
   * @return null
   */
  public function setWebDir($webDir)
  {
    $this->webDir = $webDir;
  }

  /**
   * Get uri
   * @return string
   */
  public function getUri()
  {
    return $this->uri;
  }

  /**
   * Set uri
   * @param  string
   * @return null
   */
  public function setUri($uri)
  {
    $this->uri = $uri;
  }
}
