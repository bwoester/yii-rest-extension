<?php

/**
 * Description of WwwFormUrlencodedReader
 *
 * @author Benjamin
 */
class WwwFormUrlencodedReader extends ReaderBase
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
        $result = $_POST;
        break;
    case IMessageReader::PUT:
        $body = file_get_contents( 'php://input' );
        parse_str( $body, $result );
        break;
    // TODO: are there other methods that carry a body?
    }

    return $result;
  }

}
