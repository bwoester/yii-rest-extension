<?php

/**
 *
 * @author Benjamin
 */
interface IRestRenderer
{
  public function getContentType();

  /**
   * @return string rendering result
   */
  public function renderResource( IRestResource $resource );

  /**
   * @return string rendering result
   */
  public function renderResourceList(RestResourceList $resourceList );
}
