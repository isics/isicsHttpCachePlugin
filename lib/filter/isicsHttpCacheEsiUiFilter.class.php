<?php
class isicsHttpCacheEsiUiFilter extends sfFilter
{
  public function execute(sfFilterChain $filterChain)
  {
    // Only execute filter for ESI component rendering
    $actionName = $this->getContext()->getActionName();
    if ($this->isFirstCall() && ('renderComponent' === $actionName || 'renderPartial' === $actionName))
    {
      // Continue chain
      $filterChain->execute();

      // Decorator template
      $id = uniqid();
      $html =
'
<div id="main_'.$id.'" style="border: 1px solid #f00">
  <div id="sub_main_'.$id.'" style="position:absolute;z-index:995;opacity:0.5;font-size:0.7em;background-color: #ff9; border-right: 1px solid #f00; border-bottom: 1px solid #f00;" onmouseover="this.previousOpacity=this.style.opacity;this.style.opacity=1;" onmouseout="this.style.opacity=this.previousOpacity;">
    <div style="padding: 2px;" id="sub_main_info_'.$id.'">
      [x-guid]&nbsp;%s<br />
      [x-docuri]&nbsp;%s<br />
      [x-symfony-view]&nbsp;%s<br />
      <span style="text-align:right;"><a href="#" onclick="this.parentNode.parentNode.parentNode.parentNode.style.border=\'none\';this.parentNode.parentNode.style.display=\'none\';return false;">close</a></span>
    </div>
  </div>
<div>
%s
</div></div>
';

      // Decorate content
      $response = $this->getContext()->getResponse();
      $response->setContent(sprintf($html,
        $response->getHttpHeader('x-guid'),
        $response->getHttpHeader('x-docuri'),
        $response->getHttpHeader('x-symfony-view'),
        $response->getContent())
      );

      // Make sure decorated block is not cached
      $response->setHttpHeader('Pragma', 'no-cache');
    }
    else
    {
      $filterChain->execute();
    }
  }
}