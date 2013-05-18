<?php

/**
 * Description of RestFilterEvent
 *
 * @author Benjamin
 */
class RestFilterEvent extends CEvent
{
  public function __construct( $sender=null, array $params=array() )
  {
    if (!array_key_exists('filters',$params)) {
      $params['filters'] = array();
    }

    parent::__construct( $sender, $params );
  }

  public function getFilters() {
    return $this->params['filters'];
  }

  public function addFilters( array $filters )
  {
    $this->params['filters'] = array_merge(
      $this->params['filters'],
      $filters
    );
  }
}
