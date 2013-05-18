<?php

/**
 * Description of RestControllerDefaultAccessRulesBehavior
 *
 * @author Benjamin
 */
class RestControllerDefaultAccessRulesBehavior extends RestControllerBehavior
{
  public function accessRules()
  {
    $restController   = $this->getRestController();
    $resourceId       = $restController->getResourceId();
    $currentActionId  = $restController->getAction()->getId();

    $accessRules = array(
      // everyone is allowed to view the api
      array(
        'allow',
        'actions' => array('index'),
        'users'   => array('*'),
      ),
      // user is allowed to run the current action, if he has access to an
      // authItem called "rest_{currentAction}_{resourceId}".
      // Examples:
      //  - rest_list_posts
      //  - rest_create_posts
      //  - rest_view_posts
      //  - rest_update_posts
      //  - rest_delete_posts
      array(
        'allow',
        'actions' => array( $currentActionId ),
        'roles'   => array( "rest_{$currentActionId}_{$resourceId}" ),
      ),
    );

    return $accessRules;
  }
}
