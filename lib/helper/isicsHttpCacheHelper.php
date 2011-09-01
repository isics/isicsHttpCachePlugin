<?php
/*
 * This file is part of the isicsHttpCachePlugin package.
 * Copyright (c) 2011 Isics <contact@isics.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Inserts ESI include tag instead of component content
 * accepts the same args as include_component()
 *
 * @see include_component()
 *
 * @author Nicolas Charlot <nicolas.charlot@isics.fr>
 */
function include_component_esi($moduleName, $componentName, $vars = array())
{
  $context = sfContext::getInstance();
  
  if (sfConfig::get('app_isics_http_cache_plugin_esi_enabled', false)
    && 'abc=ESI/1.0' === $context->getRequest()->getHttpHeader('Surrogate-Capability'))
  { 
    $src  = url_for(sprintf('@isics_http_cache_esi_render_component?module_name=%s&component_name=%s', $moduleName, $componentName));
    $src .= '?vars='.urlencode(serialize($vars));
  
    echo tag('esi:include', array('src' => $src));    
    
    $context->getResponse()->setHttpHeader('Surrogate-Control', 'ESI/1.0');
  }
  else
  {
    unset($vars['cache']);    
    return include_component($moduleName, $componentName, $vars);
  }
}

/**
 * Inserts ESI include tag instead of partial content
 * accepts the same args as include_partial()
 *
 * @see include_partial()
 *
 * @author Nicolas Charlot <nicolas.charlot@isics.fr>
 */
function include_partial_esi($templateName, $vars = array())
{
  $context = sfContext::getInstance();  
  
  if (sfConfig::get('app_isics_http_cache_plugin_esi_enabled', false)
    && 'abc=ESI/1.0' === $context->getRequest()->getHttpHeader('Surrogate-Capability'))  
  {
    // partial is in another module?
    if (false !== $sep = strpos($templateName, '/'))
    {
      $moduleName   = substr($templateName, 0, $sep);
      $templateName = substr($templateName, $sep + 1);
    }
    else
    {
      $moduleName = $context->getActionStack()->getLastEntry()->getModuleName();
    }
    
    $src  = url_for(sprintf('@isics_http_cache_esi_render_partial?module_name=%s&template_name=%s', $moduleName, $templateName));
    $src .= '?vars='.urlencode(serialize($vars));
    
    echo tag('esi:include', array('src' => $src));
    
    $context->getResponse()->setHttpHeader('Surrogate-Control', 'ESI/1.0');
  }
  else
  {
    unset($vars['cache']);
    return include_partial($templateName, $vars);
  }
}