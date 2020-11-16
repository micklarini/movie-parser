<?php

namespace App;

final class StatsListDeps extends \App\base\BaseListDeps
{
  protected const TABLE_NAME = 'movies_stats';
  protected const ID_FIELD = 'id';
  protected const DISPLAY_FIELD = 'votes';
  protected const PARENT_FIELD = 'movie_id';
  protected const ORDER_FIELDS = ['rate DESC'];

  use \App\base\DBListTrait;

  function __construct(\App\System $system, $parentId)
  {
    parent::__construct($system, $parentId);
    $this->generateList([self::PARENT_FIELD . " = " . $parentId]);
  }

}
