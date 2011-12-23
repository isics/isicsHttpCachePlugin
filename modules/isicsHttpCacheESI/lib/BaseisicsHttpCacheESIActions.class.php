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
  	$esi_configuration = sfConfig::get('app_isics_http_cache_plugin_esi');
    if (!isset($esi_configuration['enabled']) || true !== $esi_configuration['enabled'])
    {
      throw new sfException('ESI not enabled!');
    }

    if (!in_array($this->request->getRemoteAddress(), $esi_configuration['allowed_ips']))
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

    $this->setLayout(false);
  }

  /**
   * Handles definition of custom HTTP headers facilitating granular cache invalidation.
   *
   * - x-symfony-viewname : This header can be used to invalidate cache objects when
   * a component / partial template has been modified.
   * Invalidation example (varnish) : ban.url obj.http.x-symfony-viewname == product/someview
   * - x-docuri : This header can be used to invalidate cache objects when a document attributes
   * have changed in datastore.
   * Header value is identical to component variable $_docUri
   * Invalidation example (varnish) : ban.url obj.http.x-docuri == product/123
   * - x-guid : This header can be used to invalidate the cache object corresponding to the component / partial
   * currently executed.
   * Header value is identical to component variable $_guid. If it is not set, it is automatically generated using uniqid()
   * Invalidation example (varnish) : ban.url obj.http.x-guid == the_guid
   */
  public function postExecute()
  {
    // x-symfony-viewname
    $this->getResponse()->setHttpHeader(
    'x-symfony-view',
          sprintf('%s/%s', $this->getRequest()->getParameter('module_name'),
            $this->getRequest()->getParameter('component_name', $this->getRequest()->getParameter('template_name')))
    );

    // x-docuri
    if (isset($this->vars['_docUri']))
    {
    $this->getResponse()->setHttpHeader('x-docuri', $this->vars['_docUri']);
    }

    // x-guid
    if (!isset($this->vars['_guid']))
    {
    	$this->vars['_guid'] = uniqid();
    }
    $this->getResponse()->setHttpHeader('x-guid', $this->vars['_guid']);

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
