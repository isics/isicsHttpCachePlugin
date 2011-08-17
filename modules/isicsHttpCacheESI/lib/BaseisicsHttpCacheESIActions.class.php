<?php
/*
 * This file is part of the isicsHttpCachePlugin package.
 * Copyright (c) 2011 Isics <contact@isics.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class BaseisicsHttpCacheESIActions extends sfActions
{ 
  
  /**
   * Renders a partial or a component for a reverse proxy (Varnish or other)
   *
   * @param sfWebRequest $request  a web request
   *
   * @author Nicolas Charlot <nicolas.charlot@isics.fr>
   */
  public function executeRender(sfWebRequest $request)
  {
    if (!sfConfig::get('app_isics_http_cache_plugin_esi_enabled', false))
    {
      throw new sfException('ESI not enabled!');
    }
    
    if (!in_array($this->request->getRemoteAddress(), sfConfig::get('app_isics_http_cache_plugin_esi_allowed_ips', array('127.0.0.1'))))
    {
      throw new sfException(sprintf('IP %s not allowed!', $this->request->getRemoteAddress()));
    }    
    
    $this->params = unserialize($request->getParameter('params'));
    
    if (isset($this->params['vars']['cache']))
    {
      // Expiration:
      if (is_int($this->params['vars']['cache']))
      {
        $this->getResponse()->setMaxAge($this->params['vars']['cache']);
      }

      // Validation:
      else
      {
        // Validation with Last-Modified header:        
        if (method_exists($this->params['vars']['cache'], 'getLastModified'))
        {
          $this->getResponse()->setLastModified(call_user_func(array($this->params['vars']['cache'], 'getLastModified'), $this->params['vars']));
        }
        
        // Validation with ETag header:        
        if (method_exists($this->params['vars']['cache'], 'getETag'))
        {
          $this->getResponse()->setETag(call_user_func(array($this->params['vars']['cache'], 'getETag'), $this->params['vars']));
        }
        
        if ($this->getResponse()->isNotModified($request))
        {
          return sfView::NONE;
        }
      }
      
      unset($this->params['vars']['cache']);
    }
    
    $this->setLayout(false);
    
    // If it's a partial to render (otherwise it's a component)
    if (isset($this->params['template_name']))
    {
      return $this->renderPartial($this->params['template_name'], $this->params['vars']);      
    }
  }
}