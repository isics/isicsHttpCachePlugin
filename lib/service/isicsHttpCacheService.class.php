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
        $event->getSubject()->setHttpHeader('Cache-Control', 'public, max-age='.$event['arguments'][0]);
        // @todo try to find a way to avoid set-cookies (cause gateway cache disabling)
        return true;
      
      default:
        return false;
    }
  }
  
  static public function purge($url)
  {
    // @todo
  }
}