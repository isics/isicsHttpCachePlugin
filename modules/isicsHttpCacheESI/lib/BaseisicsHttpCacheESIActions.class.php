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
   * Handles cache expiration and validation
   *
   * @author Nicolas Charlot <nicolas.charlot@isics.fr>
   */  
  public function preExecute()
  {
    if (!sfConfig::get('app_isics_http_cache_plugin_esi_enabled', false))
    {
      throw new sfException('ESI not enabled!');
    }
    
    if (!in_array($this->request->getRemoteAddress(), sfConfig::get('app_isics_http_cache_plugin_esi_allowed_ips', array('127.0.0.1'))))
    {
      throw new sfException(sprintf('IP %s not allowed!', $this->request->getRemoteAddress()));
    }
    
    $this->vars = unserialize($this->request->getParameter('vars'));
    
    if (isset($this->vars['cache']))
    {
      // Expiration:
      if (is_int($this->vars['cache']))
      {
        $this->getResponse()->setMaxAge($this->vars['cache']);
      }

      // Validation:
      else
      {
        // Validation with Last-Modified header:        
        if (method_exists($this->vars['cache'], 'getLastModified'))
        {
          $this->getResponse()->setLastModified(call_user_func(array($this->vars['cache'], 'getLastModified'), $this->vars));
        }
        
        // Validation with ETag header:        
        if (method_exists($this->vars['cache'], 'getETag'))
        {
          $this->getResponse()->setETag(call_user_func(array($this->vars['cache'], 'getETag'), $this->vars));
        }
        
        if ($this->getResponse()->isNotModified($this->request))
        {
          return sfView::NONE;
        }
      }
      
      unset($this->vars['cache']);
    }

    // Store custom HTTP headers that can be used for granulary purging cache
    /*
     * x-symfony-viewname : can be used to invalidate corresponding cache objects when a component/partial
     * template has been changed. eg. (varnish) : ban.url obj.http.x-symfony-viewname == product/someview
     */
    $this->getResponse()->setHttpHeader(
      'x-symfony-view',
      sprintf('%s/%s', $this->getRequest()->getParameter('module_name'),
        $this->getRequest()->getParameter('component_name', $this->getRequest()->getParameter('template_name')))
    );

    /*
     * x-docuri : can be used to invalidated corresponding cache objects when attributes have changed in the datastore.
     * eg. (varnish) : ban.url obj.http.x-docuri == product/123
     */
    if (isset($this->vars['_docUri']))
    {
      $this->getResponse()->setHttpHeader('x-docuri', $this->vars['_docUri']);
    }

    $this->setLayout(false);
  }
  
  /**
   * Renders a component for a reverse proxy (Varnish or another one)
   *
   * @param sfWebRequest $request  a web request
   *
   * @author Nicolas Charlot <nicolas.charlot@isics.fr>
   */
  public function executeRenderComponent(sfWebRequest $request)
  {
    // Note: we don't use camel case cause members moduleName already exists
    $this->module_name    = $request->getParameter('module_name');
    $this->component_name = $request->getParameter('component_name');
  }

  /**
   * Renders a partial for a reverse proxy (Varnish or another one)
   *
   * @param sfWebRequest $request  a web request
   *
   * @author Nicolas Charlot <nicolas.charlot@isics.fr>
   */
  public function executeRenderPartial(sfWebRequest $request)
  {
    return $this->renderPartial(
      $request->getParameter('module_name').'/'.$request->getParameter('template_name'),
      $this->vars
    );
  }
}
