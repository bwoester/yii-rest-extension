<?php

/**
 * Description of RestResourceEvent
 *
 * @author Benjamin
 */
class RestResourceEvent extends CEvent
{
  public function __construct( $sender, IRestResource $resource )
  {
    parent::__construct( $sender, $resource );
  }

  /**
   * @return IRestResource
   */
  public function getResource()
  {
    return $this->params;
  }
}
