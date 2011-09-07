<?php

class isicsHttpCacheInvalidateTask extends sfBaseTask
{
  protected function configure()
  {
    $this->addArguments(array(
      new sfCommandArgument('url', sfCommandArgument::REQUIRED, 'Url pattern')
    ));
    
    $this->addOption('method', null, sfCommandOption::PARAMETER_REQUIRED, 'Method', 'PURGE');
    
    $this->namespace           = 'isics-http-cache';
    $this->name                = 'invalidate';
    $this->briefDescription    = 'Invalidates url(s) via an HTTP BAN or PURGE requests.';
    $this->detailedDescription = <<<EOF
The [isics-http-cache:invalidate|INFO] task Invalidates url(s) via an HTTP or PURGE requests.
Call it with:

  [php symfony isics-http-cache:invalidate|INFO]
EOF;
  }

  protected function execute($arguments = array(), $options = array())
  {
    if (isicsHttpCacheService::invalidate($arguments['url'], $options['method']))
    {
      $this->logSection('baned or purged', $arguments['url']);
    }
    else
    {
      $this->logSection('not in cache', $arguments['url'], 'ERROR');
    }
  }
}