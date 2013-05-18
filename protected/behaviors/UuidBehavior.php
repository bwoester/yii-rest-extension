<?php

/**
 * Description of UuidBehavior
 *
 * @author Benjamin
 */
class UuidBehavior extends CActiveRecordBehavior
{
  public $column = 'id';
  private $_uuid = '';

  public function beforeSave($event)
	{
    if ($this->owner->isNewRecord
      && $this->owner->hasAttribute($this->column)
      && $this->owner->getAttribute($this->column) === null)
    {
      $db = $this->owner->dbConnection;
      $command = $db->createCommand('SELECT UUID()');
      /* @var $reader CDbDataReader */
      $reader = $command->query();
      $this->_uuid = $reader->readColumn(0);

      $this->owner->setAttribute( $this->column, $this->_uuid );
    }
    else {
      $this->_uuid = '';
    }
	}

  public function afterSave($event)
  {
    if ($this->_uuid !== '')
    {
      $this->owner->setAttribute( $this->column, $this->_uuid );
      $this->_uuid = '';
    }
  }
}
