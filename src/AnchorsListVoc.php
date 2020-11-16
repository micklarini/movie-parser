<?php

namespace App;

final class AnchorsListVoc extends \App\base\BaseListVoc
{
  protected const TABLE_NAME = 'anchors';
  protected const ID_FIELD = 'id';
  protected const DISPLAY_FIELD = 'name';
  protected const ORDER_FIELDS = ['name ASC'];

  use \App\base\DBListTrait;

}
