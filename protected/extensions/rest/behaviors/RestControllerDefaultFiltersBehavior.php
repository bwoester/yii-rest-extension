<?php

/**
 * Description of RestControllerDefaultFiltersBehavior
 *
 * @author Benjamin
 */
class RestControllerDefaultFiltersBehavior extends RestControllerBehavior
{
  public function filters()
  {
    return array(
      array( 'GetOnlyFilter + index, list, view' ),
      array( 'PostOnlyFilter + create' ),
      array( 'PutOnlyFilter + update' ),
      array( 'DeleteOnlyFilter + delete' ),
      array( 'ValidateIdParamFilter + delete, update, view' ),
      array( 'ValidatePageParamFilter + list' ),
      array(
        'ValidateSizeParamFilter + list',
        'restController' => $this->getRestController(),
      ),
      'accessControl',
    );
  }
}

class DeleteOnlyFilter extends CFilter
{
  protected function preFilter( $filterChain )
  {
    return Yii::app()->getRequest()->getIsDeleteRequest();
  }
}

class GetOnlyFilter extends CFilter
{
  protected function preFilter( $filterChain )
  {
    return isset( $_SERVER['REQUEST_METHOD'] )
      && !strcasecmp( $_SERVER['REQUEST_METHOD'], 'GET' );
  }
}

class PostOnlyFilter extends CFilter
{
  protected function preFilter( $filterChain )
  {
    return Yii::app()->getRequest()->getIsPostRequest();
  }
}

class PutOnlyFilter extends CFilter
{
  protected function preFilter( $filterChain )
  {
    return Yii::app()->getRequest()->getIsPutRequest();
  }
}

/**
 * Ensures that $_GET['id'] is either a positive integer, or a uuid string
 * in the format [0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}
 */
class ValidateIdParamFilter extends CFilter
{
  protected function preFilter( $filterChain )
  {
    // Check we have data. If we don't have data, throw an exception.
    if (!array_key_exists('id',$_GET)) {
      throw new CHttpException( 400, 'No resource id provided!' );
    }

    $id     = $_GET['id'];
    $regExp = '/^' . RestHelper::getRegExpSupportedIds() . '$/';

    if (preg_match($regExp,$id) === 1) {
      return true;
    }
    else {
      throw new CHttpException( 400, 'Unrecognized id format!' );
    }
  }
}

/**
 * Ensures that $_GET['page'] is an integer > 0
 */
class ValidatePageParamFilter extends CFilter
{
  protected function preFilter( $filterChain )
  {
    // Ensure we have data. Get value from query, or use defaul page.
    $page = array_key_exists( 'page', $_GET ) ? $_GET['page'] : 1;

    // Ensure we work on integer data
    $page = CPropertyValue::ensureInteger( $page );

    // Ensure we work on positive integer data greater zero
    $page = $page <= 0 ? 1 : $page;

    // Write valid data back to GET params
    $_GET['page'] = $page;

    return true;
  }
}

/**
 * Ensures that $_GET['size'] is an integer in the range
 * [ 1, RestController::$maxPageSize ]
 */
class ValidateSizeParamFilter extends CFilter
{
  /**
   * @var RestController
   */
  public $restController = null;

  protected function preFilter( $filterChain )
  {
    // Ensure we have data. Get value from query, or use defaul page size.
    $pageSize = array_key_exists( 'size', $_GET )
      ? $_GET['size']
      : $this->restController->defaultPageSize;

    // Ensure we work on integer data
    $pageSize = CPropertyValue::ensureInteger( $pageSize );

    // Ensure we work on positive integer data greater zero
    $pageSize = $pageSize <= 0
      ? $this->restController->defaultPageSize
      : $pageSize;

    // Ensure the page size doesn't exceed our maximum
    $pageSize = $pageSize > $this->restController->maxPageSize
      ? $this->restController->defaultPageSize
      : $pageSize;

    // Write valid data back to GET params
    $_GET['size'] = $pageSize;

    return true;
  }
}
