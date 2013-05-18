<?php

/**
 * Description of RestRendererJson
 *
 * @author Benjamin
 */
class RestRendererJson extends CComponent implements IRestRenderer
{

  public function renderResource( IRestResource $resource )
  {
    self::prepareRestResource( $resource );
    // $resource is a stdClass now!
    return CJSON::encode( $resource );
  }

  public function renderResourceList( RestResourceList $resourceList )
  {
    $aResources = $resourceList->toArray();
    array_walk( $aResources, array('RestRendererJson','prepareRestResource') );
    // $aResources is an array of stdClass now!
    return CJSON::encode( $aResources );
  }

  /**
   * This method will convert IRestResource into an object for serialization!
   *
   * @param IRestResource $restResource
   * @param type $key
   */
  public static function prepareRestResource( IRestResource & $restResource, $key=null ) {
    $restResource = (object)$restResource->getAttributes();
  }

  public function getContentType()
  {
    return 'application/json';
  }

}
