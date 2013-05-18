<?php

/**
 * This is the model class for table "photo".
 *
 * The followings are the available columns in table 'photo':
 * @property string $uuid
 *
 * The followings are the available model relations:
 * @property Image[] $images
 */
class Photo extends CActiveRecord
{
  /**
   * @var CUploadedFile
   */
  public $uploadedFile = null;

	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return Photo the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'photo';
	}

  public function behaviors()
  {
    return array(
      'uuid'  => array(
        'class'   => 'application.behaviors.UuidBehavior',
        'column'  => 'uuid',
      ),
    );
  }

  /**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
    $reUuid = "[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}";

		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
      array( 'uploadedFile', 'required', 'on' => 'create' ),
			array( 'uuid', 'unsafe' ),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array( 'uuid', 'safe', 'on'=>'search' ),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array(
			'images' => array(self::HAS_MANY, 'Image', 'photo_uuid'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'uuid' => 'Uuid',
		);
	}

	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 * @return CActiveDataProvider the data provider that can return the models based on the search/filter conditions.
	 */
	public function search()
	{
		// Warning: Please modify the following code to remove attributes that
		// should not be searched.

		$criteria=new CDbCriteria;

		$criteria->compare('uuid',$this->uuid,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}