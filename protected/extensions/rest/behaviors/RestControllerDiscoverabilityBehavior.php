<?php

/**
 * Description of RestControllerDiscoverabilityBehavior
 *
 * @author Benjamin
 */
class RestControllerDiscoverabilityBehavior extends RestControllerBehavior
{
  const REL_NEXT  = 'next';
  const REL_PREV  = 'prev';
  const REL_FIRST = 'first';
  const REL_LAST  = 'last';
  const REL_COLLECTION  = 'collection';

  public function linkTitles() {
    return array(
      self::REL_FIRST => 'First page',
      self::REL_LAST => 'Last page',
      self::REL_NEXT => 'Next page',
      self::REL_PREV => 'Previous page',
      self::REL_COLLECTION => 'Resource collection',
    );
  }

  public function handlePagedResourceRetrieval( RestPagedResourceEvent $event )
  {
    $resourceList   = $event->getResourceList();
    $apiController  = $this->getRestController();
    $linkHeader     = $apiController->getHeader('Link');
    $aLinkHeaders   = $linkHeader === '' ? array() : array($linkHeader);

    if ($resourceList->hasNextPage())
    {
      $uri = $this->constructNextPageUri( $resourceList );
      $aLinkHeaders[] = $this->createLinkHeader( $uri, self::REL_NEXT );
    }

    if ($resourceList->hasPreviousPage())
    {
      $uri = $this->constructPrevPageUri( $resourceList );
      $aLinkHeaders[] = $this->createLinkHeader( $uri, self::REL_PREV );
    }

    if ($resourceList->hasFirstPage())
    {
      $uri = $this->constructFirstPageUri( $resourceList );
      $aLinkHeaders[] = $this->createLinkHeader( $uri, self::REL_FIRST );
    }

    if ($resourceList->hasLastPage())
    {
      $uri = $this->constructLastPageUri( $resourceList );
      $aLinkHeaders[] = $this->createLinkHeader( $uri, self::REL_LAST );
    }

    $linkHeader = implode( ', ', $aLinkHeaders );
    if ($linkHeader !== '') {
      $apiController->setHeader( 'Link', $linkHeader );
    }
  }

  public function handleSingleResourceRetrieval( RestResourceEvent $event )
  {
    $this->addCollectionLinkHeader();
  }

  public function handleResourceCreated(RestResourceEvent $event)
  {
    $this->setLocationHeader( $event->getResource() );
    $this->addCollectionLinkHeader();
  }

  public function handleResourceUpdated( RestResourceEvent $event )
  {
    $this->addCollectionLinkHeader();
  }

  protected function addCollectionLinkHeader()
  {
    $apiController  = $this->getRestController();
    $linkHeader     = $apiController->getHeader('Link');
    $aLinkHeaders   = $linkHeader === '' ? array() : array($linkHeader);

    $uri = $this->constructCollectionUri();
    $aLinkHeaders[] = $this->createLinkHeader( $uri, self::REL_COLLECTION );

    $linkHeader = implode( ', ', $aLinkHeaders );
    if ($linkHeader !== '') {
      $apiController->setHeader( 'Link', $linkHeader );
    }
  }

  protected function setLocationHeader( IRestResource $resource )
  {
    $uri = $this->constructLocationUri( $resource );
    $this->getRestController()->setHeader( 'Location', $uri );
  }

  private function constructNextPageUri( RestResourceList $resourceList )
  {
    return $this->getRestController()->createAbsoluteUrl( 'list', array(
      'resource'  => $this->getRestController()->getResourceId(),
      'page'      => $resourceList->getPages()->getCurrentPage() + 2,
      'size'      => $resourceList->getPages()->getPageSize(),
    ));
  }

  private function constructPrevPageUri( RestResourceList $resourceList )
  {
    return $this->getRestController()->createAbsoluteUrl( 'list', array(
      'resource'  => $this->getRestController()->getResourceId(),
      'page'      => $resourceList->getPages()->getCurrentPage(),
      'size'      => $resourceList->getPages()->getPageSize(),
    ));
  }

  private function constructFirstPageUri( RestResourceList $resourceList )
  {
    return $this->getRestController()->createAbsoluteUrl( 'list', array(
      'resource'  => $this->getRestController()->getResourceId(),
      'page'      => 1,
      'size'      => $resourceList->getPages()->getPageSize(),
    ));
  }

  private function constructLastPageUri( RestResourceList $resourceList )
  {
    return $this->getRestController()->createAbsoluteUrl( 'list', array(
      'resource'  => $this->getRestController()->getResourceId(),
      'page'      => $resourceList->getPages()->getPageCount(),
      'size'      => $resourceList->getPages()->getPageSize(),
    ));
  }

  private function constructCollectionUri()
  {
    return $this->getRestController()->createAbsoluteUrl( 'list', array(
      'resource'  => $this->getRestController()->getResourceId(),
    ));
  }

  private function constructLocationUri( IRestResource $resource )
  {
    return $this->getRestController()->createAbsoluteUrl( 'view', array(
      'resource'  => $this->getRestController()->getResourceId(),
      'id'        => $resource->getId(),
    ));
  }

  private function createLinkHeader( $uri, $rel )
  {
    $aLinkTitles = $this->linkTitles();
    $title = array_key_exists( $rel, $aLinkTitles )
      ? $aLinkTitles[$rel]
      : false;
    return $title
      ? "<$uri>; rel=\"$rel\"; title=\"$title\""
      : "<$uri>; rel=\"$rel\"";
  }
}
