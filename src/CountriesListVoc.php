<?php

namespace App;

final class CountriesListVoc extends \App\base\BaseListVoc
{
  protected const TABLE_NAME = 'countries';
  protected const ID_FIELD = 'id';
  protected const DISPLAY_FIELD = 'name';
  protected const ORDER_FIELDS = ['name ASC'];

  use \App\base\DBListTrait;

}
