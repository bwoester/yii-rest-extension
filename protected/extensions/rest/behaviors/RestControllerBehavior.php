<?php

/**
 * Allows to configure filters and access rules for REST resources.
 *
 * @author Benjamin
 */
class RestControllerBehavior extends CBehavior
{

	public function attach( $owner )
  {
    if (!$owner instanceof RestController) {
      throw new CException( 'RestBehaviors are only meant to be attached to ApiController!' );
    }

    parent::attach($owner);
  }

  /**
   * @return RestController
   */
  public function getRestController() {
    return $this->getOwner();
  }

  /**
   * Declares events and the corresponding event handler methods.
   * If you override this method, make sure you merge the parent result to the return value.
   * @return array events (array keys) and the corresponding event handler methods (array values).
   * @see CBehavior::events
  */
  public function events()
  {
    return array(
      'onConfigureFilters'        => 'configureFilters',
      'onConfigureAccessRules'    => 'configureAccessRules',
      'onPagedResourceRetrieval'  => 'handlePagedResourceRetrieval',
      'onSingleResourceRetrieval' => 'handleSingleResourceRetrieval',
      'onResourceCreated'         => 'handleResourceCreated',
      'onResourceUpdated'         => 'handleResourceUpdated',
      'onResourceDeleted'         => 'handleResourceDeleted',
    );
  }

  public function configureFilters( RestFilterEvent $event )
  {
    $event->addFilters( $this->filters() );
  }

  public function configureAccessRules( RestAccessRulesEvent $event )
  {
    $event->addAccessRules( $this->accessRules() );
  }

  public function filters()
  {
    return array();
  }

  public function accessRules()
  {
    return array();
  }

  public function handlePagedResourceRetrieval(RestPagedResourceEvent $event)
  {
  }

  public function handleSingleResourceRetrieval(RestResourceEvent $event)
  {
  }

  public function handleResourceCreated(RestResourceEvent $event)
  {
  }

  public function handleResourceUpdated(RestResourceEvent $event)
  {
  }

  public function handleResourceDeleted(RestResourceEvent $event)
  {
  }

}
