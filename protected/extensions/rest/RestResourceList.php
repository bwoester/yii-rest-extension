<?php

/**
 * Description of RestModelList
 *
 * @author Benjamin
 */
class RestResourceList extends CTypedList
{
  /**
   * @var CPagination
   */
  private $_pages = null;

	public function __construct( CPagination $pages )
	{
    parent::__construct( 'IRestResource' );
    $this->_pages = $pages;
	}

  /**
   * @return CPagination
   */
  public function getPages() {
    return $this->_pages;
  }

  public function hasNextPage()
  {
    $currentPage  = $this->getPages()->getCurrentPage();
    $pageCount    = $this->getPages()->getPageCount();
    return $currentPage < $pageCount - 1;
  }

  public function hasPreviousPage() {
    return $this->getPages()->getCurrentPage() > 0;
  }

  public function hasFirstPage() {
    return $this->hasPreviousPage();
  }

  public function hasLastPage()
  {
    return $this->getPages()->getPageCount() > 1
      && $this->hasNextPage();
  }
}
