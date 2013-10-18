<?php

class isicsHttpCacheInvalidateTask extends sfBaseTask
{
  protected function configure()
  {
    $this->addArguments(array(
      new sfCommandArgument('host', sfCommandArgument::REQUIRED, 'Host'),
      new sfCommandArgument('url', sfCommandArgument::REQUIRED, 'Url pattern')
    ));

    $this->addOptions(array(
      new sfCommandOption('method', null, sfCommandOption::PARAMETER_REQUIRED, 'Method', 'PURGE'),
      new sfCommandOption('application', null, sfCommandOption::PARAMETER_REQUIRED, 'The application name', 'superbackend'),
      new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'prod'),
    ));

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
    isicsHttpCacheService::invalidate($arguments['host'], $arguments['url'], $options['method']);
    $this->logSection('banned or purged');
  }
}