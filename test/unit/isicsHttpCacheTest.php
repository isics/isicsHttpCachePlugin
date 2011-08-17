<?php

/*
 * This file is part of the isicsHttpCachePlugin package.
 * Copyright (c) 2011 Isics <contact@isics.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once(sfConfig::get('sf_root_dir').'/test/bootstrap/unit.php');

class myWebRequest extends sfWebRequest
{
  public function setHttpHeader($name, $value)
  {
    $name = 'HTTP_'.strtoupper(strtr($name, '-', '_'));
    if (null === $value)
    {
      unset($this->pathInfoArray[$name]);
    }
    else
    {
      $this->pathInfoArray[$name] = $value;      
    }
  }
}

$t = new lime_test(21);

$event_dispatcher = sfProjectConfiguration::getActive()->getEventDispatcher();

$request  = new myWebRequest($event_dispatcher);
$response = new sfWebResponse($event_dispatcher);

$t->diag('Testing new methods of sfWebRequest');
$request->setHttpHeader('If-None-Match', 'TEST');
$t->is($request->getETags(), array('TEST'), 'sfWebRequest::getETags method added and working');
$request->setHttpHeader('If-None-Match', null);

$t->diag('Testing new methods of sfWebResponse');

$response->setMaxAge(600);
$t->is($response->getHttpHeader('Cache-Control'), 'public, max-age=600', 'sfWebResponse::setMaxAge() method added an working');

$response->setETag('TEST');
$t->is($response->getHttpHeader('ETag'), 'TEST', 'sfWebResponse::setETag() method added an working');

$response->setLastModified($now = date('r'));
$t->is($response->getHttpHeader('Last-Modified'), $now, 'sfWebResponse::setLastModified() method added an working');
$response->setLastModified(null);

$t->ok(!$response->isNotModified($request), 'sfWebResponse::isNotModified() method added and working with a request without ETags or If-Modified-Since headers');

$request->setHttpHeader('If-None-Match', 'TEST');
$response->setETag('TEST');
$t->ok($response->isNotModified($request), 'sfWebResponse::isNotModified() working with same ETags');
$t->is($response->getStatusCode(), 304, 'sfWebResponse::isNotModified() change the status to 304');
$request->setHttpHeader('If-None-Match', null);
$response->setETag(null);
$response->setStatusCode(200);

$request->setHttpHeader('If-None-Match', 'TEST2');
$response->setETag('TEST');
$t->ok(!$response->isNotModified($request), 'sfWebResponse::isNotModified() working with different ETags');
$t->is($response->getStatusCode(), 200, 'sfWebResponse::isNotModified() let the status 200');
$request->setHttpHeader('If-None-Match', null);
$response->setETag(null);

$request->setHttpHeader('If-Modified-Since', $now = date('r'));
$response->setLastModified($now);
$t->ok($response->isNotModified($request), 'sfWebResponse::isNotModified() working with same Last-Modified');
$t->is($response->getStatusCode(), 304, 'sfWebResponse::isNotModified() change the status to 304');
$request->setHttpHeader('If-Modified-Since', null);
$response->setLastModified(null);
$response->setStatusCode(200);

$request->setHttpHeader('If-Modified-Since', date('r'));
$response->setLastModified(date('r', strtotime('yesterday')));
$t->ok(!$response->isNotModified($request), 'sfWebResponse::isNotModified() working with different Last-Modified');
$t->is($response->getStatusCode(), 200, 'sfWebResponse::isNotModified() let the status 200');
$request->setHttpHeader('If-Modified-Since', null);
$response->setLastModified(null);

$request->setHttpHeader('If-None-Match', 'TEST');
$request->setHttpHeader('If-Modified-Since', date('r'));
$response->setETag('TEST');
$response->setLastModified(date('r', strtotime('yesterday')));
$t->ok(!$response->isNotModified($request), 'sfWebResponse::isNotModified() working with same ETags but different Last-Modified');
$t->is($response->getStatusCode(), 200, 'sfWebResponse::isNotModified() let the status 200');
$request->setHttpHeader('If-Modified-Since', null);
$request->setHttpHeader('If-None-Match', null);
$response->setETag(null);
$response->setLastModified(null);

$request->setHttpHeader('If-None-Match', 'TEST');
$request->setHttpHeader('If-Modified-Since', $now = date('r'));
$response->setETag('TEST2');
$response->setLastModified($now);
$t->ok(!$response->isNotModified($request), 'sfWebResponse::isNotModified() working with different ETags but same Last-Modified');
$t->is($response->getStatusCode(), 200, 'sfWebResponse::isNotModified() let the status 200');
$request->setHttpHeader('If-Modified-Since', null);
$request->setHttpHeader('If-None-Match', null);
$response->setETag(null);
$response->setLastModified(null);

$request->setHttpHeader('If-None-Match', 'TEST');
$request->setHttpHeader('If-Modified-Since', $now = date('r'));
$response->setETag('TEST');
$response->setLastModified($now);
$t->ok($response->isNotModified($request), 'sfWebResponse::isNotModified() working with same ETags and same Last-Modified');
$t->is($response->getStatusCode(), 304, 'sfWebResponse::isNotModified() change the status to 304');
$request->setHttpHeader('If-Modified-Since', null);
$request->setHttpHeader('If-None-Match', null);
$response->setETag(null);
$response->setLastModified(null);
$response->setStatusCode(200);

$request->setHttpHeader('If-None-Match', 'TEST');
$request->setHttpHeader('If-Modified-Since', date('r'));
$response->setETag('TEST2');
$response->setLastModified(date('r', strtotime('yesterday')));
$t->ok(!$response->isNotModified($request), 'sfWebResponse::isNotModified() working with different ETags and different Last-Modified');
$t->is($response->getStatusCode(), 200, 'sfWebResponse::isNotModified() let the status 200');
$request->setHttpHeader('If-Modified-Since', null);
$request->setHttpHeader('If-None-Match', null);
$response->setETag(null);
$response->setLastModified(null);