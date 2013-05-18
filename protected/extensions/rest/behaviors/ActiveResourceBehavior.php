<?php

/**
 * This behavior allows CActiveRecord instances to be used as IRestResource
 * instances.
 *
 * @code
 * $model = new MyActiveRecord();
 * $model->attachBehavior( 'ActiveResourceBehavior', 'ext.rest.behaviors.ActiveResourceBehavior' );
 * $restResource = $model->asa( 'ActiveResourceBehavior' );
 * @endcode
 *
 * @author Benjamin
 */
class ActiveResourceBehavior extends CActiveRecordBehavior implements IRestResource
{

  public function count()
  {
    return $this->owner->count();
  }

  public function delete()
  {
    return $this->owner->delete();
  }

  public function getAttributes( $names = null )
  {
    return $this->owner->getAttributes( $names );
  }

  /**
   * @return IRestResource
   * @TODO throw exception if not found
   */
  public function getById( $id )
  {
    /* @var $model CActiveRecord */
    $model = $this->owner->findByPk( $id );
    $model->attachBehavior( 'ActiveResourceBehavior', 'ext.rest.behaviors.ActiveResourceBehavior' );
    return $model->asa('ActiveResourceBehavior');
  }

  public function getId()
  {
    return $this->owner->getPrimaryKey();
  }

  /**
   * @return RestResourceList
   */
  public function getList( CPagination $pages )
  {
    $list = new RestResourceList( $pages );
    $pages->applyLimit( $this->ownerModel()->getDbCriteria() );
    $aModels = $this->ownerModel()->findAll();

    foreach ($aModels as $model)
    {
      /* @var $model CActiveRecord */
      $model->attachBehavior( 'ActiveResourceBehavior', 'ext.rest.behaviors.ActiveResourceBehavior' );
      $list->add( $model->asa('ActiveResourceBehavior') );
    }

    return $list;
  }

  /**
   * @return IRestResource
   */
  public function newInstance()
  {
    $arClassName = get_class( $this->owner );
    /* @var $model CActiveRecord */
    $model = new $arClassName();
    $model->attachBehavior( 'ActiveResourceBehavior', 'ext.rest.behaviors.ActiveResourceBehavior' );
    return $model->asa('ActiveResourceBehavior');
  }

  // TODO throw exception if validation fails
  // TODO throw exception if save fails
  public function save()
  {
    $saved = $this->owner->save();

    if (!$saved)
    {
      // TODO provide all errors in the exception?
      $errorMsg = '';
      foreach ($this->owner->getErrors() as $attributeName => $aErrors)
      {
        foreach ($aErrors as $error) {
          $errorMsg = "$attributeName: $error";
          break;
        }

        if ($errorMsg !== '') {
          break;
        }
      }

      throw new CHttpException( 400, $errorMsg );
    }

    return;
  }

  public function setAttributes( $values, $safeOnly = true )
  {
    $this->owner->setAttributes( $values, $safeOnly );
  }

  private function ownerModel()
  {
    return call_user_func(array(
      get_class( $this->owner ),
      'model'
    ));
  }

  public function setScenario($scenario)
  {
    $this->owner->setScenario($scenario);
  }

}
