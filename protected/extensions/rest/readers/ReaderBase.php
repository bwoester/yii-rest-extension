<?php

/**
 * Description of ReaderBase
 *
 * @author Benjamin
 */
abstract class ReaderBase extends CComponent implements IMessageReader
{
  private $_requestMethod = '';

  public function setRequestMethod( $method ) {
    $this->_requestMethod = $method;
  }

  public function getRequestMethod() {
    return $this->_requestMethod;
  }
}
