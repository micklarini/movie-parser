<?php

namespace App;

final class HistoryListDeps extends \App\base\BaseListDeps
{
  protected const TABLE_NAME = 'movies_history';
  protected const ID_FIELD = 'id';
  protected const DISPLAY_FIELD = 'position';
  protected const PARENT_FIELD = 'movie_id';
  protected const ORDER_FIELDS = ['mark_date DESC'];

  use \App\base\DBListTrait;

  function __construct(\App\System $system, $parentId)
  {
    parent::__construct($system, $parentId);
    $this->generateList([self::PARENT_FIELD . " = " . $parentId]);
  }

}
