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
    $src  = url_for('@default?module=isicsHttpCacheESI&action=render');
    $src .= '?params='.urlencode(serialize(array('module_name' => $moduleName, 'component_name' => $componentName, 'vars' => $vars)));
  
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
    $src  = url_for('@default?module=isicsHttpCacheESI&action=render');
    $src .= '?params='.urlencode(serialize(array('template_name' => $templateName, 'vars' => $vars)));
    
    echo tag('esi:include', array('src' => $src));
    
    $context->getResponse()->setHttpHeader('Surrogate-Control', 'ESI/1.0');
  }
  else
  {
    unset($vars['cache']);
    return include_partial($templateName, $vars);
  }
}