<?php

// uncomment the following to define a path alias
// Yii::setPathOfAlias('local','path/to/local-folder');

Yii::setPathOfAlias( 'restExtension', dirname(__FILE__).'/../extensions/rest' );
Yii::import('restExtension.RestHelper', true);

$reId = RestHelper::getRegExpSupportedIds();

// This is the main Web application configuration. Any writable
// CWebApplication properties can be configured here.
return array(
	'basePath'=>dirname(__FILE__).DIRECTORY_SEPARATOR.'..',
	'name'=>'My Web Application',

	// preloading 'log' component
	'preload'=>array('log'),

	// autoloading model and component classes
	'import'=>array(
		'application.models.*',
		'application.components.*',
	),

  'controllerMap'=>array(
    'api' => array(
      'class' => 'ext.rest.RestController',
      'behaviors' => array(
        'defaultAccessRules'  => 'ext.rest.behaviors.RestControllerDefaultAccessRulesBehavior',
        'defaultFilters'      => 'ext.rest.behaviors.RestControllerDefaultFiltersBehavior',
        'discoverability'     => 'ext.rest.behaviors.RestControllerDiscoverabilityBehavior',

        // grant access to all actions (from localhost) during development
        'developmentAccessRules'  => 'ext.rest.behaviors.RestControllerDevelopmentAccessRulesBehavior',
      ),
      'messageReaders' => array(
        'application/json'                  => 'ext.rest.readers.JsonReader',
        'application/x-www-form-urlencoded' => 'ext.rest.readers.WwwFormUrlencodedReader',
      ),
      'resources' => array(
        'photos'  => 'application.models.Photo',
        'users'   => 'application.models.restResources.User',
      ),
      'resourceAdapters' => array(
        'RestResourceBase' => 'ext.rest.behaviors.RestResourceBehavior',
        'CActiveRecord'    => 'ext.rest.behaviors.ActiveResourceBehavior',
      ),
      'modelAccessors' => array(
        'CActiveRecord' => array('{resource}', 'model'),
      ),
    ),
  ),

	'modules'=>array(
		// uncomment the following to enable the Gii tool
		'gii'=>array(
			'class'     =>  'system.gii.GiiModule',
			'password'  =>  'le82hG4',
		 	// If removed, Gii defaults to localhost only. Edit carefully to taste.
			'ipFilters' =>  array( '127.0.0.1', '::1' ),
      //'ipFilters' =>  false,
		),
	),

	// application components
	'components'=>array(
		'user'=>array(
			// enable cookie-based authentication
			'allowAutoLogin'=>true,
		),
    'urlManager'=>array(
      'urlFormat'=>'path',
      'rules'=>array(

        // REST patterns
        array('api/list'  , 'pattern'=>"api/<resource:\w+>"           , 'verb'=>'GET'   ),
        array('api/view'  , 'pattern'=>"api/<resource:\w+>/<id:$reId>", 'verb'=>'GET'   ),
        array('api/update', 'pattern'=>"api/<resource:\w+>/<id:$reId>", 'verb'=>'PUT'   ),
        array('api/delete', 'pattern'=>"api/<resource:\w+>/<id:$reId>", 'verb'=>'DELETE'),
        array('api/create', 'pattern'=>"api/<resource:\w+>"           , 'verb'=>'POST'  ),

        // Other controllers
        '<controller:\w+>/<action:\w+>'=>'<controller>/<action>',
      ),
    ),
		'db'=>array(
			'connectionString'  => 'mysql:host=localhost;dbname=rest_test',
			'emulatePrepare'    => true,
			'username'          => 'rstTstUsr',
			'password'          => 'le82hG4',
			'charset'           => 'utf8',
		),
		'errorHandler'=>array(
      // use 'site/error' action to display errors
      'errorAction'=>'site/error',
    ),
		'log'=>array(
			'class'=>'CLogRouter',
			'routes'=>array(
				array(
					'class'=>'CFileLogRoute',
					'levels'=>'error, warning',
				),
        array(
          'class'     => 'ext.yii-debug-toolbar.YiiDebugToolbarRoute',
          'ipFilters' => array('*'),
        ),
				// uncomment the following to show log messages on web pages
				/*
				array(
					'class'=>'CWebLogRoute',
				),
				*/
			),
		),
	),

	// application-level parameters that can be accessed
	// using Yii::app()->params['paramName']
	'params'=>array(
		// this is used in contact page
		'adminEmail'=>'webmaster@example.com',
	),
);