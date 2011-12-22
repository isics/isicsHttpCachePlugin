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

    if (!in_array($this->request->getRemoteAddress(), $esi_configuration['allowed_ips'], array('127.0.0.1')))
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