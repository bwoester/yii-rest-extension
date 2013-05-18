<?php

/**
 *
 * @author Benjamin
 */
interface IMessageReader
{
  const CONNECT = 'CONNECT';
  const DELETE  = 'DELETE';
  const GET     = 'GET';
  const HEAD    = 'HEAD';
  const OPTIONS = 'OPTIONS';
  const PATCH   = 'PATCH';
  const POST    = 'POST';
  const PUT     = 'PUT';
  const TRACE   = 'TRACE';

  public function setRequestMethod( $method );

  /**
   * Reads the message and transforms it into an array of attribute => value
   * pairs.
   *
   * @return array of attribute => value pairs
   */
  public function readMessage();
}
