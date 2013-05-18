<?php

/**
 *
 * @author Benjamin
 */
interface IRestResource
{
  public function getAttributes( $names=null );
  public function setAttributes( $values, $safeOnly=true );

  public function setScenario( $scenario );

  public function getId();

  /**
   * @return IRestResource
   */
  public function newInstance();
  public function count();

  /**
   * @return IRestResource
   */
  public function getById( $id );

  /**
   * @return RestResourceList
   */
  public function getList( CPagination $pages );

  public function save();
  public function delete();
}
