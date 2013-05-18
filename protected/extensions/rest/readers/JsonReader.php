<?php

/**
 * Description of JsonReader
 *
 * @author Benjamin
 */
class JsonReader extends ReaderBase
{

  /**
   * Reads the message and transforms it into an array of attribute => value
   * pairs.
   *
   * @return array of attribute => value pairs
   */
  public function readMessage()
  {
    $result = array();
    $requestMethod = $this->getRequestMethod();

    switch ($requestMethod)
    {
    case IMessageReader::POST:
    case IMessageReader::PUT:
        $body = file_get_contents( 'php://input' );
        $result = CJSON::decode( $body, true );
        break;
    // TODO: are there other methods that carry a body?
    }

    return $result;
  }

}
