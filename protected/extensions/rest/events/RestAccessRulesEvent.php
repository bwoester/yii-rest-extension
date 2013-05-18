<?php

/**
 * Description of RestAccessRulesEvent
 *
 * @author Benjamin
 */
class RestAccessRulesEvent extends CEvent
{
  public function __construct( $sender=null, array $params=array() )
  {
    if (!array_key_exists('accessRules',$params)) {
      $params['accessRules'] = array();
    }

    parent::__construct( $sender, $params );
  }

  public function getAccessRules() {
    return $this->params['accessRules'];
  }

  public function addAccessRules( array $accessRules )
  {
    $this->params['accessRules'] = array_merge(
      $this->params['accessRules'],
      $accessRules
    );
  }
}
