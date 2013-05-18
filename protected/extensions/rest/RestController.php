<?php

Yii::import( 'ext.rest.*' );
Yii::import( 'ext.rest.behaviors.*' );
Yii::import( 'ext.rest.events.*' );
Yii::import( 'ext.rest.interfaces.*' );
Yii::import( 'ext.rest.readers.*' );
Yii::import( 'ext.rest.renderers.*' );

/**
 * @see "http://www.yiiframework.com/wiki/175/how-to-create-a-rest-api"
 * @see "http://restful-api-design.readthedocs.org/en/latest/intro.html"
 * @see "http://www.baeldung.com/rest-with-spring-series/"
 * @see "http://blog.programmableweb.com/2011/11/18/rest-api-design-putting-the-type-in-content-type/"
 */
class RestController extends CController
{
  public $defaultPageSize = 20;
  public $maxPageSize     = 100;

  public $defaultCreateScenario  = 'rest-create';
  public $defaultViewScenario    = 'rest-view';
  public $defaultUpdateScenario  = 'rest-update';
  public $defaultDeleteScenario  = 'rest-delete';

  public $resourceScenarios = array(
    'photos'  => array(
      'create'  =>  'create',
      'view'    =>  'view',
      'update'  =>  'update',
      'delete'  =>  'delete',
    ),
  );

  /**
   * Cache to store resource models.
   * @var array
   */
  private $_resourceModels = array();

  public $restActionsPath   = 'application.actions.rest';
  public $restBehaviorsPath = 'application.behaviors.rest';

  /**
   * These behaviors are different from normal behaviors in the way they are
   * attached to the RestController. Normal behaviors will always be attached.
   * RestBehaviors will only be attached if they match the resourceId that is
   * currently processed.
   *
   * @var array of RestBehavior configurations (resourceId => config)
   */
  public $restBehaviors = array();

  public $behaviors = array();

  /**
   * Dictionary of rest resources.
   * [resource id] => [resource class alias]
   * @var array
   */
  public $resources = array();

  /**
   * Adapters are behaviors, that implement the IRestResource interface.
   * They can be used to use any CComponent as a RestResource, without
   * modifying it. One adapter class that comes with the rest extension is the
   * ActiveResourceBehavior. You can configure the resourceAdapters to contain:
   *
   *   'CActiveRecord' => 'ext.rest.behaviors.ActiveResourceBehavior',
   *
   * This means, whenever the RestController receives a request to work on a
   * resource that is mapped to an instance of CActiveRecord (@see resources),
   * it will not try to use this instance directly as a RestResource. Instead,
   * it will attach the configured behavior to the instance and work on the
   * behavior.
   *
   * @var array [base class] => [adapter alias]
   */
  public $resourceAdapters = array();

  /**
   * @var array
   */
  public $modelAccessors = array();

  /**
   * Access rule configurations for this controller.
   * Will be merged with pre-defined access rules and collected access rules.
   *
   * @see onConfigureAccessRules()
   * @var array
   */
  public $accessRules = array();

  /**
   * Filter configurations for this controller.
   * Will be merged with pre-defined filters and collected filters.
   *
   * @see onConfigureFilters()
   * @var array
   */
  public $filters = array();

  /**
   * Message readers are used to read the request body of every request.
   * @var array of message reader configurations
   */
  public $messageReaders = array();

  /**
   * Cache of message readers instances
   * @var array (content-type => message reader instance)
   */
  private $_messageReaders = array();

  /**
   * Stores http headers that should be send to client before the rendering
   * output.
   *
   * @var array
   */
  private $_responseHeaders = array();

  private $_requestHeaders = array();

  /**
   * Flag introduced for behaviors. Since we provide the possibility to
   * configure controller behaviors, we don't want behaviors to be attached in
   * constructor, but when the controller has been configured.
   *
   * Life cycle (@see "CWebApplication::runController")
   *  - construct
   *  - configure
   *  - init()
   *  - run()
   *
   * @var boolean
   */
  private $_initialized = false;

  /////////////////////////////////////////////////////////////////////////////

  public function init()
  {
    parent::init();

    Yii::app()->onError = array( $this, 'handleError' );
    Yii::app()->onException = array( $this, 'handleException' );

    $this->_initialized = true;
    $this->attachBehaviors( $this->behaviors() );
  }

  /////////////////////////////////////////////////////////////////////////////

  /**
   * Returns behavior configurations.
   *
   * The implementation will use configured behaviors and extend it with some
   * built-in behaviors. It will then lookup behavior configuration from the
   * restBehaviors property. If no behavior for the current resource is found
   * in restBehaviors property, the implementation will search for a matching
   * REST behavior for the current resource in restBehaviorsPath. REST behavior
   * classes will only be found if they accord to the naming convention of
   * 'Rest{ResourceId}Behavior'.
   *
   * @return array
   */
  public function behaviors()
  {
    if (!$this->_initialized) {
      return array();
    }

    // configured behaviors
    $behaviors = $this->behaviors;

    // merge rest behaviors - configured rest behaviors
    $resourceId = $this->getResourceId();
    if (array_key_exists($resourceId, $this->restBehaviors))
    {
      $behaviors[] = $this->restBehaviors[$resourceId];
    }
    // merge rest behaviors - search rest behaviors
    else
    {
      $classNameTmpl = 'Rest{ResourceId}Behavior';
      $className = str_replace( '{ResourceId}', ucfirst($resourceId), $classNameTmpl );
      $filepath = Yii::getPathOfAlias($this->restBehaviorsPath) . '/' . $className . '.php';

      if (file_exists($filepath)) {
        $behaviors[] = $this->restBehaviorsPath . '.' . $className;
      }
    }

    return $behaviors;
  }

  /////////////////////////////////////////////////////////////////////////////

  /**
   * This event is raised when filters are being configured.
   * @param RestFilterEvent $event the event parameter
   */
  public function onConfigureFilters( RestFilterEvent $event )
  {
    $this->raiseEvent( 'onConfigureFilters', $event );
  }

  /////////////////////////////////////////////////////////////////////////////

  /**
   * This event is raised when filters are being configured.
   * @param RestFilterEvent $event the event parameter
   */
  public function onConfigureAccessRules( RestAccessRulesEvent $event )
  {
    $this->raiseEvent( 'onConfigureAccessRules', $event );
  }

  /////////////////////////////////////////////////////////////////////////////

  /**
   * This event is raised when a paged collection is retrieved (list action).
   * @param RestPagedResourceEvent $event the event parameter
   */
  public function onPagedResourceRetrieval( RestPagedResourceEvent $event )
  {
    $this->raiseEvent( 'onPagedResourceRetrieval', $event );
  }

  /////////////////////////////////////////////////////////////////////////////

  /**
   * This event is raised when a resource is retrieved (view action).
   * @param RestResourceEvent $event the event parameter
   */
  public function onSingleResourceRetrieval( RestResourceEvent $event )
  {
    $this->raiseEvent( 'onSingleResourceRetrieval', $event );
  }

  /////////////////////////////////////////////////////////////////////////////

  /**
   * This event is raised when a resource is created (create action).
   * @param RestResourceEvent $event the event parameter
   */
  public function onResourceCreated( RestResourceEvent $event )
  {
    $this->raiseEvent( 'onResourceCreated', $event );
  }

  /////////////////////////////////////////////////////////////////////////////

  /**
   * This event is raised when a resource is updated (update action).
   * @param RestResourceEvent $event the event parameter
   */
  public function onResourceUpdated( RestResourceEvent $event )
  {
    $this->raiseEvent( 'onResourceUpdated', $event );
  }

  /////////////////////////////////////////////////////////////////////////////

  /**
   * This event is raised when a resource is deleted (delete action).
   * @param RestResourceEvent $event the event parameter
   */
  public function onResourceDeleted( RestResourceEvent $event )
  {
    $this->raiseEvent( 'onResourceDeleted', $event );
  }

  /////////////////////////////////////////////////////////////////////////////

  /**
   * @return array action filters
   */
  public function filters()
  {
    // fire event to allow RestControllerBehaviors to define filters
    $event = new RestFilterEvent( $this );
    $this->onConfigureFilters( $event );

    // merge configured and collected filters
    $filters = array_merge( $this->filters, $event->getFilters() );
    return $filters;
  }

  /////////////////////////////////////////////////////////////////////////////

  /**
   * Returns the access rules for this controller.
   *
   * The impplementation will merge the configured access rules from the
   * $accessRules member with those collected by the onConfigureAccessRules
   * event. It will also append a "deny-all" rule to the very end of the
   * accessRules event, so if something ist explicitly allowed, it will be
   * denied.
   *
   * @return array
   */
  public function accessRules()
  {
    // fire event to allow RestControllerBehaviors to define access rules
    $event = new RestAccessRulesEvent($this);
    $this->onConfigureAccessRules( $event );

    // merge configured and collected access rules
    $accessRules = array_merge( $this->accessRules, $event->getAccessRules() );

    // for max security, append a "deny-all" rule. If something isn't
    // explicitly allowed, deny it.
    $accessRules[] = array('deny');

    return $accessRules;
  }

  /////////////////////////////////////////////////////////////////////////////

  public function getResourceId()
  {
    return array_key_exists('resource', $_GET)
      ? $_GET['resource']
      : '';
  }

  /////////////////////////////////////////////////////////////////////////////

  /**
   * Returns a resource model.
   *
   * This can be any class implementing IRestResource, or an instance of
   * CActiveRecord. In case of an CActiveRecord, the ActiveResourceBehavior
   * will be attached and the active record instance will be returned 'as a'
   * ActiveResourceBehavior, which in turn implements IRestResource.
   *
   * @return IRestResource
   */
  private function getResourceModel()
  {
    $resourceModel = null;
    $resourceId = $this->getResourceId();

    // serve from cache if found
    if (array_key_exists($resourceId,$this->_resourceModels))
    {
      $resourceModel = $this->_resourceModels[ $resourceId ];
    }
    else
    {
      $resourceModel = call_user_func( $this->getModelAccessor() );

      // everything's fine
      if ($resourceModel instanceof IRestResource) {
      }
      // use an adapter if one is provided
      else
      {
        $adapter = $this->getResourceAdapter();

        if ($adapter instanceof IBehavior && $adapter instanceof IRestResource)
        {
          $resourceModel->attachBehavior( 'IRestResource', $adapter );
          $resourceModel = $resourceModel->asa( 'IRestResource' );
        }
        else
        {
          $className = get_class( $resourceModel );
          throw new CHttpException( 500, "'$className' can't be used as REST resource! Either implement IRestResource, or configure a resouece adapter!" );
        }
      }

      $this->_resourceModels[ $resourceId ] = $resourceModel;
    }

    return $resourceModel;
  }

  /////////////////////////////////////////////////////////////////////////////

  private function getResourceAlias()
  {
    $resourceId = $this->getResourceId();
    return $this->resources[ $resourceId ];
  }

  /////////////////////////////////////////////////////////////////////////////

  private function getResourceClassName()
  {
    $resourceAlias = $this->getResourceAlias();
    $aPieces = explode( '.', $resourceAlias );
    return end( $aPieces );
  }

  /////////////////////////////////////////////////////////////////////////////

  /**
   * @return callable
   */
  private function getModelAccessor()
  {
    $accessor = false;
    $resourceClassName = $this->getResourceClassName();

    $className = $resourceClassName;
    while (is_string($className))
    {
      if (array_key_exists($className, $this->modelAccessors))
      {
        $accessor = $this->modelAccessors[$className];

        if (is_array($accessor))
        {
          if (array_key_exists(0,$accessor) && is_string($accessor[0])) {
            $accessor[0] = str_replace( '{resource}', $resourceClassName, $accessor[0] );
          }

          if (array_key_exists(1,$accessor) && is_string($accessor[1])) {
            $accessor[1] = str_replace( '{resource}', $resourceClassName, $accessor[1] );
          }
        }

        break;
      }
      // walk up the class hierarchy
      else
      {
        $className = get_parent_class( $className );
      }
    }

    if (!is_callable($accessor)) {
      $accessor = create_function( '', "return new $resourceClassName();" );
    }

    return $accessor;
  }

  /////////////////////////////////////////////////////////////////////////////

  /**
   * @return mixed CComponent implementing IBehavior or false
   */
  private function getResourceAdapter()
  {
    $adapter = false;
    $resourceClassName = $this->getResourceClassName();

    $className = $resourceClassName;
    while (is_string($className))
    {
      if (array_key_exists($className, $this->resourceAdapters))
      {
        $adapterConfig = $this->resourceAdapters[$className];
        $adapter = Yii::createComponent( $adapterConfig );
        break;
      }
      // walk up the class hierarchy
      else
      {
        $className = get_parent_class( $className );
      }
    }

    return $adapter;
  }

  /////////////////////////////////////////////////////////////////////////////

  public function setHeader( $key, $value ) {
    $this->_responseHeaders[$key] = $value;
  }

  /////////////////////////////////////////////////////////////////////////////

  public function getHeader( $key )
  {
    return array_key_exists($key, $this->_responseHeaders)
      ? $this->_responseHeaders[$key]
      : '';
  }

  /////////////////////////////////////////////////////////////////////////////

  /**
   * Override parent implementation and use custom RestAccessControlFilter
   * class instead of framework's AccessControlFilter. Problem with
   * AccessControlFilter is, that it will automatically redirect to the login
   * page if an action is denied. We only want to throw a machine readable
   * error.
   */
  public function filterAccessControl( $filterChain )
  {
    $filter = new RestAccessControlFilter();
    $filter->setRules( $this->accessRules() );
    $filter->filter( $filterChain );
  }

  /////////////////////////////////////////////////////////////////////////////

  public function actionIndex()
  {
    $api = new stdClass();
    $api->version = '1.0';
    $api->resources = array();

    $hex    = '[0-9a-f]';
    $reUuid = "$hex{8}-$hex{4}-$hex{4}-$hex{4}-$hex{12}";

    foreach (array_keys($this->resources) as $key)
    {
      $idField = new stdClass();
      $idField->name    = 'id';
      $idField->type    = 'string';
      $idField->min     = null;
      $idField->max     = null;
      $idField->minlen  = 36;
      $idField->maxlen  = 36;
      $idField->regex   = $reUuid;

      $listMethod = new stdClass();
      $listMethod->name         = 'list';
      $listMethod->method       = 'GET';
      $listMethod->action       = $this->createAbsoluteUrl( $key );
      $listMethod->fields       = array();
      $listMethod->constraints  = array();

      $viewMethod = new stdClass();
      $viewMethod->name         = 'view';
      $viewMethod->method       = 'GET';
      $viewMethod->action       = $this->createAbsoluteUrl( $key ) . '/{id}';
      $viewMethod->fields       = array(
        $idField,
      );
      $viewMethod->constraints  = array();

      $createMethod = new stdClass();
      $createMethod->name         = 'create';
      $createMethod->method       = 'POST';
      $createMethod->action       = $this->createAbsoluteUrl( $key );
      $createMethod->fields       = array();
      $createMethod->constraints  = array();

      $updateMethod = new stdClass();
      $updateMethod->name         = 'update';
      $updateMethod->method       = 'PUT';
      $updateMethod->action       = $this->createAbsoluteUrl( $key ) . '/{id}';
      $updateMethod->fields       = array(
        $idField,
      );
      $updateMethod->constraints  = array();

      $deleteMethod = new stdClass();
      $deleteMethod->name         = 'delete';
      $deleteMethod->method       = 'DELETE';
      $deleteMethod->action       = $this->createAbsoluteUrl( $key ) . '/{id}';
      $deleteMethod->fields       = array(
        $idField,
      );
      $deleteMethod->constraints  = array();

      $resource = new stdClass();
      $resource->name         = $key;
      $resource->fields       = array();
      $resource->connections  = array();
      $resource->methods      = array(
        $listMethod, $viewMethod, $createMethod, $updateMethod, $deleteMethod,
      );

      $api->resources[] = $resource;
    }

    $this->_sendResponse(
      200,
      CJSON::encode($api),
      'application/json'
    );
  }

  /////////////////////////////////////////////////////////////////////////////

  /**
   * Reads the accept header from the clients request. This way, the client can
   * specify which content type it prefers.
   *
   * @return array of mime types sorted by preference.
   */
  private function getAcceptedMediaTypes()
  {
    // TODO: implement
    // @see "http://www.xml.com/pub/a/2005/06/08/restful.html"
    // @see "http://jrgns.net/parse_http_accept_header"
    // @see "http://bililite.com/blog/2010/01/06/parsing-the-http-accept-header/"
//    $request = Yii::app()->getRequest();
//    $acceptTypes = $request->getAcceptTypes();
    return array();
  }

  /////////////////////////////////////////////////////////////////////////////

  private function getRequestMethod()
  {
    if (!array_key_exists('REQUEST_METHOD',$_SERVER)) {
      throw new CHttpException( 500, "Unable to determine request method!" );
    }

    return $_SERVER['REQUEST_METHOD'];
  }

  /////////////////////////////////////////////////////////////////////////////

  /**
   * @param string $contentType
   * @return IMessageReader
   * @throws CHttpException
   */
  private function getMessageReader( $contentType='' )
  {
    $contentType = $contentType === ''
      ? $this->getRequestContentType()
      : $contentType;

    if (array_key_exists($contentType, $this->_messageReaders)) {
      return $this->_messageReaders[$contentType];
    }

    $messageReaderConfig = array_key_exists($contentType, $this->messageReaders)
      ? $this->messageReaders[$contentType]
      : array();

    try
    {
      $messageReader = Yii::createComponent( $messageReaderConfig );

      if ($messageReader instanceof IMessageReader)
      {
        $messageReader->setRequestMethod( $this->getRequestMethod() );
        $this->_messageReaders[$contentType] = $messageReader;
      }
      else
      {
        throw new CHttpException( 500, "Message reader for content type '$contentType' must implement 'IMessageReader'!" );
      }
    }
    catch ( CException $e )
    {
      Yii::log( $e->getMessage(), CLogger::LEVEL_ERROR );
      throw new CHttpException( 500, "Can't create message reader for messages of type '$contentType'!" );
    }

    return $this->_messageReaders[$contentType];
  }

  /////////////////////////////////////////////////////////////////////////////

  private function getRequestContentType( $default='application/x-www-form-urlencoded' )
  {
    $requestHeaders = $this->getRequestHeaders();
    return array_key_exists('Content-Type', $requestHeaders)
      ? $requestHeaders['Content-Type']
      : $default;
  }

  /////////////////////////////////////////////////////////////////////////////

  private function getRequestHeaders()
  {
    if (!empty($this->_requestHeaders)) {
      return $this->_requestHeaders;
    }

    $this->_requestHeaders = array();

    foreach ($_SERVER as $key => $value)
    {
      if (substr($key,0,5) !== "HTTP_") {
        continue;
      }

      $key = str_replace( '_', ' ', substr($key,5) );
      $key = str_replace( ' ', '-', ucwords(strtolower($key)) );

      $this->_requestHeaders[$key] = $value;
    }

    return $this->_requestHeaders;
  }

  /////////////////////////////////////////////////////////////////////////////

  /**
   * Returns the most appropriate renderer (once getAcceptedMediaTypes() is
   * implemented). Currently, it will return getFallbackRenderer().
   * @return IRestRenderer
   */
  private function getRenderer()
  {
    $renderer   = null;
    $acceptList = $this->getAcceptedMediaTypes();

    // find renderer for the acccepted media types
    foreach ($acceptList as $mediaType)
    {
      if (array_key_exists($mediaType, $this->renderers)) {
        $renderer = Yii::createComponent( $this->renderers[$mediaType] );
      }
    }

    // if there is no renderer for the acccepted media types, fall back.
    if (!$renderer instanceof IRestRenderer) {
      $renderer = $this->getFallbackRenderer();
    }

    return $renderer;
  }

  /////////////////////////////////////////////////////////////////////////////

  private function getFallbackRenderer()
  {
    return Yii::createComponent( 'ext.rest.renderers.RestRendererJson' );
  }

  /////////////////////////////////////////////////////////////////////////////

  /**
   * Override parent implementation. This is basically done to allow the
   * well-known workflow in actions:
   *
   *  - load model
   *  - modify model
   *  - validate/ save model
   *  - renderPartial result
   *
   * However, instead of using views, this implementation utilizes renderers
   * to format and output data.
   *
   * @param string $view
   * @param array $data
   * @param boolean $return
   * @param boolean $processOutput
   * @return mixed string if $return is true, void otherwise
   */
  public function renderPartial( $view, $data = null, $return = false, $processOutput = false )
  {
    $renderer = $this->getRenderer();

    switch ($view)
    {
    case 'list':
        $output = $renderer->renderResourceList( $data['resourceList'] );
        break;
    case 'view':
        $output = $renderer->renderResource( $data['resource'] );
        break;
    default:
        // TODO raise event
    }

    if ($processOutput) {
      $output = $this->processOutput( $output );
    }

    if ($return) {
      return $output;
    } else {
      $this->setHeader( 'Content-Type', $renderer->getContentType() );
      $this->renderHeaders();
      echo $output;
    }
  }

  /////////////////////////////////////////////////////////////////////////////

  private function renderHeaders()
  {
    foreach ($this->_responseHeaders as $key => $value) {
      header("$key: $value");
    }
  }

  /////////////////////////////////////////////////////////////////////////////

  public function actionList( $page=1, $size=20 )
  {
    $resourceModel = $this->getResourceModel();

    $pages = new CPagination( $resourceModel->count() );
    $pages->setPageSize( $size );
    $pages->setCurrentPage( $page-1 );

    $list = $resourceModel->getList( $pages );

    $event = new RestPagedResourceEvent( $this, $list );
    $this->onPagedResourceRetrieval( $event );

    $this->renderPartial( 'list', array(
      'resourceList'  => $list,
    ));
  }

  /////////////////////////////////////////////////////////////////////////////

  public function actionView( $id )
  {
    $resourceModel = $this->getResourceModel();

    $resource = $resourceModel->getById( $id );
    $resource->setScenario( $this->defaultViewScenario );

    $event = new RestResourceEvent( $this, $resource );
    $this->onSingleResourceRetrieval( $event );

    $this->renderPartial( 'view', array(
      'resource'  => $resource,
    ));
  }

  /////////////////////////////////////////////////////////////////////////////

  public function actionCreate()
  {
    $messageReader = $this->getMessageReader();
    $message = $messageReader->readMessage();

    $resourceModel = $this->getResourceModel();

    $resource = $resourceModel->newInstance();
    $resource->setScenario( $this->defaultCreateScenario );
    $resource->setAttributes( $message );
    $resource->save();

    $event = new RestResourceEvent( $this, $resource );
    $this->onResourceCreated( $event );

    $this->renderPartial( 'view', array(
      'resource'  => $resource,
    ));

//    switch ($_GET['resource'])
//    {
//      // Get an instance of the respective resource
//      case 'posts':
//        $model = new Post;
//        break;
//      default:
//        $this->_sendResponse(501, sprintf('Mode <b>create</b> is not implemented for resource <b>%s</b>', $_GET['resource']));
//        Yii::app()->end();
//    }
//
//    // Try to assign POST values to attributes
//    foreach ($_POST as $var => $value)
//    {
//      // Does the resource have this attribute? If not raise an error
//      if ($model->hasAttribute($var))
//        $model->$var = $value;
//      else
//        $this->_sendResponse(500, sprintf('Parameter <b>%s</b> is not allowed for resource <b>%s</b>', $var, $_GET['resource']));
//    }
//    // Try to save the resource
//    if ($model->save())
//      $this->_sendResponse(200, CJSON::encode($model),'application/json');
//    else
//    {
//      // Errors occurred
//      $msg = "<h1>Error</h1>";
//      $msg .= sprintf("Couldn't create resource <b>%s</b>", $_GET['resource']);
//      $msg .= "<ul>";
//      foreach ($model->errors as $attribute => $attr_errors)
//      {
//        $msg .= "<li>Attribute: $attribute</li>";
//        $msg .= "<ul>";
//        foreach ($attr_errors as $attr_error)
//          $msg .= "<li>$attr_error</li>";
//        $msg .= "</ul>";
//      }
//      $msg .= "</ul>";
//      $this->_sendResponse(500, $msg);
//    }
  }

  /////////////////////////////////////////////////////////////////////////////

  public function actionUpdate( $id )
  {
    $messageReader = $this->getMessageReader();
    $message = $messageReader->readMessage();

    $resourceModel = $this->getResourceModel();

    $resource = $resourceModel->getById( $id );
    $resource->setScenario( $this->defaultUpdateScenario );
    $resource->setAttributes( $message );
    $resource->save();

    $event = new RestResourceEvent( $this, $resource );
    $this->onResourceUpdated( $event );

    $this->renderPartial( 'view', array(
      'resource'  => $resource,
    ));

//    // Parse the PUT parameters. This didn't work: parse_str(file_get_contents('php://input'), $put_vars);
//    $json = file_get_contents('php://input'); //$GLOBALS['HTTP_RAW_POST_DATA'] is not preferred: http://www.php.net/manual/en/ini.core.php#ini.always-populate-raw-post-data
//    $put_vars = CJSON::decode($json, true);  //true means use associative array
//
//    switch ($_GET['resource'])
//    {
//      // Find respective resource
//      case 'posts':
//        $model = Post::model()->findByPk($_GET['uuid']);
//        break;
//      default:
//        $this->_sendResponse(501, sprintf('Error: Mode <b>update</b> is not implemented for resource <b>%s</b>', $_GET['resource']));
//        Yii::app()->end();
//    }
//    // Did we find the requested resource? If not, raise an error
//    if ($model === null)
//      $this->_sendResponse(400, sprintf("Error: Didn't find any resource <b>%s</b> with ID <b>%s</b>.", $_GET['resource'], $_GET['uuid']));
//
//    // Try to assign PUT parameters to attributes
//    foreach ($put_vars as $var => $value)
//    {
//      // Does resource have this attribute? If not, raise an error
//      if ($model->hasAttribute($var))
//        $model->$var = $value;
//      else
//      {
//        $this->_sendResponse(500, sprintf('Parameter <b>%s</b> is not allowed for resource <b>%s</b>', $var, $_GET['resource']));
//      }
//    }
//    // Try to save the resource
//    if ($model->save())
//      $this->_sendResponse(200, CJSON::encode($model),'application/json');
//    else
//    // prepare the error $msg
//    // see actionCreate
//    // ...
//      $this->_sendResponse(500, $msg);
  }

  /////////////////////////////////////////////////////////////////////////////

  public function actionDelete( $id )
  {
    $resourceModel = $this->getResourceModel();

    $resource = $resourceModel->getById( $id );
    $resource->setScenario( $this->defaultDeleteScenario );
    $resource->delete();

    $event = new RestResourceEvent( $this, $resource );
    $this->onResourceDeleted( $event );

    $this->renderPartial( 'view', array(
      'resource'  => $resource,
    ));

//    switch ($_GET['resource'])
//    {
//      // Load the respective resource
//      case 'posts':
//        $model = Post::model()->findByPk($_GET['uuid']);
//        break;
//      default:
//        $this->_sendResponse(501, sprintf('Error: Mode <b>delete</b> is not implemented for resource <b>%s</b>', $_GET['resource']));
//        Yii::app()->end();
//    }
//    // Was a resource found? If not, raise an error
//    if ($model === null)
//      $this->_sendResponse(400, sprintf("Error: Didn't find any resource <b>%s</b> with ID <b>%s</b>.", $_GET['resource'], $_GET['uuid']));
//
//    // Delete the resource
//    $num = $model->delete();
//    if ($num > 0)
//      $this->_sendResponse(200, $num, 'application/json');    //this is the only way to work with backbone
//    else
//      $this->_sendResponse(500, sprintf("Error: Couldn't delete resource <b>%s</b> with ID <b>%s</b>.", $_GET['resource'], $_GET['uuid']));
  }

  /////////////////////////////////////////////////////////////////////////////

  public function handleError( CErrorEvent $event )
  {
    $error = new RestError();
    $error->code = $event->code;
    $error->message = $event->message;

    if (defined('YII_DEBUG') && YII_DEBUG)
    {
      $error->file = $event->file;
      $error->line = $event->line;
      // $error->test = $event->get;
    }

    $this->_sendResponse(
      500,
      CJSON::encode($error),
      'application/json'
    );

    $event->handled = true;
  }

  /////////////////////////////////////////////////////////////////////////////

  public function handleException( CExceptionEvent $event )
  {
    $exception = $event->exception;
    $statusCode = $exception instanceof CHttpException
      ? $exception->statusCode
      : 500;

    $error = new RestError();
    $error->code = $exception->getCode();
    $error->message = $exception->getMessage();

    if (defined('YII_DEBUG') && YII_DEBUG)
    {
      $error->file = $exception->getFile();
      $error->line = $exception->getLine();
    }

    $this->_sendResponse(
      $statusCode,
      CJSON::encode($error),
      'application/json'
    );

    $event->handled = true;
  }

  /////////////////////////////////////////////////////////////////////////////

  private function _sendResponse($status = 200, $body = '', $content_type = 'text/html')
  {
    // set the status
    $status_header = 'HTTP/1.1 ' . $status . ' ' . $this->_getStatusCodeMessage($status);
    header($status_header);
    // and the content type
    header('Content-type: ' . $content_type);

    // pages with body are easy
    if ($body != '')
    {
      // send the body
      echo $body;
    }
    // we need to create the body if none is passed
    else
    {
      // create some body messages
      $message = '';

      // this is purely optional, but makes the pages a little nicer to read
      // for your users.  Since you won't likely send a lot of different status codes,
      // this also shouldn't be too ponderous to maintain
      switch ($status)
      {
        case 401:
          $message = 'You must be authorized to view this page.';
          break;
        case 404:
          $message = 'The requested URL ' . $_SERVER['REQUEST_URI'] . ' was not found.';
          break;
        case 500:
          $message = 'The server encountered an error processing your request.';
          break;
        case 501:
          $message = 'The requested method is not implemented.';
          break;
      }

      // servers don't always have a signature turned on
      // (this is an apache directive "ServerSignature On")
      $signature = ($_SERVER['SERVER_SIGNATURE'] == '') ? $_SERVER['SERVER_SOFTWARE'] . ' Server at ' . $_SERVER['SERVER_NAME'] . ' Port ' . $_SERVER['SERVER_PORT'] : $_SERVER['SERVER_SIGNATURE'];

      // this should be templated in a real-world solution
      $body = '
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
    <title>' . $status . ' ' . $this->_getStatusCodeMessage($status) . '</title>
</head>
<body>
    <h1>' . $this->_getStatusCodeMessage($status) . '</h1>
    <p>' . $message . '</p>
    <hr />
    <address>' . $signature . '</address>
</body>
</html>';

      echo $body;
    }
    Yii::app()->end();
  }

  /////////////////////////////////////////////////////////////////////////////

  private function _getStatusCodeMessage($status)
  {
    // these could be stored in a .ini file and loaded
    // via parse_ini_file()... however, this will suffice
    // for an example
    $codes = Array(
        200 => 'OK',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
    );
    return (isset($codes[$status])) ? $codes[$status] : '';
  }

  /////////////////////////////////////////////////////////////////////////////

}
