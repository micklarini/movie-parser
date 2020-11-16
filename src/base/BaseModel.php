<?php

namespace App\base;

abstract class BaseModel
{
  protected static $system;

  function __construct(\App\System $system)
  {
    self::$system = $system;
  }

  abstract public static function createFrom(array $source, \App\System $system);

}
