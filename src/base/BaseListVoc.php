<?php

namespace App\base;

class BaseListVoc
{
  protected static $system;
  protected $list;
  protected $index;

  function __construct(\App\System $system)
  {
    self::$system = $system;
    $this->list = $this->index = [];
    $this->generateList();
  }

  public function getList(): array
  {
    return $this->list;
  }

  public function getIndex(): array
  {
    return $this->index;
  }

  public function get($id)
  {
    return $this->list[$id];
  }

  public function getIndexEntry($id)
  {
    return $this->index[$id];
  }

}
