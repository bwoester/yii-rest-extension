<?php

Yii::import( 'system.web.auth.CAccessControlFilter', true );

/**
 * Description of RestAccessControlFilter
 *
 * @author Benjamin
 */
class RestAccessControlFilter extends CAccessControlFilter
{
  protected function accessDenied( $user, $message )
  {
    throw new CHttpException( 403, $message );
  }
}
