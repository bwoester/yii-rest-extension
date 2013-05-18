<?php

/**
 * Description of RestPagedResourceEvent
 *
 * @author Benjamin
 */
class RestPagedResourceEvent extends CEvent
{
  public function __construct( $sender, RestResourceList $resources )
  {
    parent::__construct( $sender, $resources );
  }

  /**
   * @return RestResourceList
   */
  public function getResourceList()
  {
    return $this->params;
  }
}
