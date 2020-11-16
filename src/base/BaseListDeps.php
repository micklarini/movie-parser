<?php

namespace App\base;

class BaseListDeps
{
  protected static $system;
  protected $parentId;
  protected $list;
  protected $index;

  function __construct(\App\System $system, $parentId)
  {
    self::$system = $system;
    $this->list = $this->index = [];
    $this->parentId = $parentId;
  }

}
