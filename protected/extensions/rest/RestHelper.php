<?php

/**
 * Description of RestHelper
 *
 * @author Benjamin
 */
class RestHelper
{
  public static function getRegExpUUID()
  {
    $hex = '[0-9a-f]';
    return "$hex{8}-$hex{4}-$hex{4}-$hex{4}-$hex{12}";
  }

  public static function getRegExpNumericId()
  {
    return '\d+';
  }

  public static function getRegExpSupportedIds()
  {
    $reUUID = self::getRegExpUUID();
    $reNumericId = self::getRegExpNumericId();
    return "({$reUUID}|{$reNumericId})";
  }

}
