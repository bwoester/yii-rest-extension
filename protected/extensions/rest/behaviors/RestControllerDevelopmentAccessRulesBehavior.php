<?php

/**
 * Description of RestControllerDefaultAccessRulesBehavior
 *
 * @author Benjamin
 */
class RestControllerDevelopmentAccessRulesBehavior extends RestControllerBehavior
{
  public function accessRules()
  {
    $accessRules = array(
      // for development, allow access to all actions from localhost
      array(
        'allow',
        'ips' =>  array( '127.0.0.1' ),
      ),
    );

    return $accessRules;
  }
}
