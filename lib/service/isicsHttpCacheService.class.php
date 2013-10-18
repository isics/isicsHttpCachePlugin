<?php
/*
 * This file is part of the isicsHttpCachePlugin package.
 * Copyright (c) 2011 Isics <contact@isics.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class isicsHttpCacheService
{
  const METHOD_BAN   = 'BAN';
  const METHOD_PURGE = 'PURGE';

  /**
   * Adds methods to sfWebResponse
   *
   * @param sfEvent $event  Event
   *
   * @return boolean
   *
   * @author Nicolas Charlot <nicolas.charlot@isics.fr>
   */
  static public function addRequestMethods(sfEvent $event)
  {
    switch ($event['method'])
    {
      case 'getETags':
        $event->setReturnValue(
          preg_split('/\s*,\s*/', $event->getSubject()->getHttpHeader('If-None-Match'), null, PREG_SPLIT_NO_EMPTY)
        );
        return true;

      default:
        return false;
    }

  }

  /**
   * Adds methods to sfWebResponse
   *
   * @param sfEvent $event  Event
   *
   * @return boolean
   *
   * @author Nicolas Charlot <nicolas.charlot@isics.fr>
   */
  static public function addResponseMethods(sfEvent $event)
  {
    switch ($event['method'])
    {
      case 'isNotModified':
        if (empty($event['arguments']) || !($event['arguments'][0] instanceof sfWebRequest))
        {
          throw new sfException('isNotModified() method requires a sfWebRequest argument.');
        }

        $last_modified = $event['arguments'][0]->getHttpHeader('If-Modified-Since');
        $not_modified  = false;

        if ($etags = $event['arguments'][0]->getETags())
        {
          $not_modified = (in_array($event->getSubject()->getHttpHeader('ETag'), $etags) || in_array('*', $etags)) && (null === $last_modified || $event->getSubject()->getHttpHeader('Last-Modified') === $last_modified);
        }
        elseif (null !== $last_modified)
        {
          $not_modified = $last_modified === $event->getSubject()->getHttpHeader('Last-Modified');
        }

        if ($not_modified)
        {
          $event->getSubject()->setStatusCode(304);
          $event->getSubject()->setContent(null);

          // remove headers that MUST NOT be included with 304 Not Modified responses
          foreach (array('Allow', 'Content-Encoding', 'Content-Language', 'Content-Length', 'Content-MD5', 'Content-Type', 'Last-Modified') as $header)
          {
            $event->getSubject()->setHttpHeader($header, null);
          }
        }

        $event->setReturnValue($not_modified);

        return true;

      case 'setETag':
        $event->getSubject()->setHttpHeader('ETag', $event['arguments'][0]);
        return true;

      case 'setLastModified':
        $event->getSubject()->setHttpHeader('Last-Modified', $event['arguments'][0]);
        return true;

      case 'setMaxAge':
        $event->getSubject()->setHttpHeader('Cache-Control', 'public, max-age='.$event['arguments'][0].(2 === count($event['arguments']) ? ', s-maxage='.$event['arguments'][1] : ''));
        // @todo try to find a way to avoid set-cookies (cause gateway cache disabling)
        return true;

      default:
        return false;
    }
  }

  /**
   * Invalidates url(s) via HTTP BAN or PURGE
   * use PURGE method for a single url and BAN for pattern
   *
   * @param string $host         host
   * @param string $url_pattern  url pattern to purge
   *                             (with Varnish, regexp is only suitable for BAN request)
   * @param string $method       method (PURGE by default)
   *
   * @author Nicolas Charlot <nicolas.charlot@isics.fr>
   */
  static public function invalidate($host, $url_pattern, $method = 'PURGE')
  {
    if (!in_array($method, array('BAN', 'PURGE')))
    {
      throw new InvalidArgumentException('Only BAN and PURGE methods supported.');
    }

    if (!function_exists('curl_init'))
    {
      throw new RuntimeException('PHP CURL support must be enabled to use purge method.');
    }

    if ((null === $servers = sfConfig::get('app_isics_http_cache_plugin_servers')) || !is_array($servers))
    {
      throw new RuntimeException('No server has been defined.');
    }

    $host = str_replace(array('http:', 'https:', '/'), '', $host);

    $options = array(
      CURLOPT_CUSTOMREQUEST  => $method,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_HEADER         => true,
      CURLOPT_NOBODY         => true,
      CURLOPT_TIMEOUT        => 2000,
      CURLOPT_HTTPHEADER     => array('Host: '.$host),
    );

    // Inspired from http://www.onlineaspect.com/2009/01/26/how-to-use-curl_multi-without-blocking/

    $mh = curl_multi_init();

    foreach ($servers as $server)
    {
      $url = 'http://'.$server['address'].':'.$server['port'].$url_pattern;

      $ch = curl_init($url);
      curl_setopt_array($ch, $options);
      curl_multi_add_handle($mh, $ch);
    }

    do {
      while (CURLM_CALL_MULTI_PERFORM === ($mrc = curl_multi_exec($mh, $running)));

      if (CURLM_OK !== $mrc)
      {
        break;
      }

      // a request was just completed -- find out which one
      while ($done = curl_multi_info_read($mh))
      {
        $info = curl_getinfo($done['handle']);

        if (200 !== $info['http_code'])
        {
          throw new RuntimeException(sprintf(
            'Unable to invalidate cache (Method: %s, Host: %s, Url: %s)',
            $method,
            $host,
            $info['url'])
          );
        }

        curl_multi_remove_handle($mh, $done['handle']);
      }
    } while ($running);

    curl_multi_close($mh);
  }
}