<?php
/*
 * This file is part of the isicsHttpCachePlugin package.
 * Copyright (c) 2011 Isics <contact@isics.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class isicsHttpCachePluginConfiguration extends sfPluginConfiguration
{
  public function initialize()
  {
    $this->dispatcher->connect('request.method_not_found', array('isicsHttpCacheService', 'addRequestMethods'));
    $this->dispatcher->connect('response.method_not_found', array('isicsHttpCacheService', 'addResponseMethods'));
  }
}