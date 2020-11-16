<?php

namespace App\base;

trait DBListTrait
{
  use CRUDTrait {
    insert as protected insertRecord;
  }

  public function list()
  {
    return $this->list;
  }

  public function item($key)
  {
    return $this->list[$key];
  }

  public function find($value)
  {
    $key = array_search($value, $this->index);
    return $key == false ? null : $key;
  }

   public function insert(string $value, \PDO $pdo = null)
  {
    $id = $this->insertRecord([self::DISPLAY_FIELD => $value]);
    if (!empty($id) && $id != 0) {
      $entry = $this->selectOneByFields([self::ID_FIELD => $id]);
      $this->list[self::ID_FIELD] = $entry;
      $this->index[self::ID_FIELD] = $entry[self::DISPLAY_FIELD];
      //$this->getList();
    }
    return $id;
  }

  protected function makeIndex()
  {
    $this->index = array_combine(
      array_keys($this->list),
      array_column($this->list, self::DISPLAY_FIELD)
    );
  }

  protected function generateList(array $filters = [])
  {
    $list = [];
    $sql = "SELECT * FROM `" . self::TABLE_NAME . '`' .
      (empty($filters) ? '' : " WHERE " . implode(' AND ', $filters)) .
      " ORDER BY " . implode(', ', self::ORDER_FIELDS);
    $stmt = self::$system->pdo->query($sql, \PDO::FETCH_ASSOC);
    foreach ($stmt as $row) {
      $this->list[$row[self::ID_FIELD]] = $row;
    }
    $this->makeIndex();
  }

  public function appendReplace(array $items, $filter = null) {
    array_walk($items, function(&$item) use($filter) {
      array_filter($item, function($k) use($filter) {
        return in_array($k, $filter);
      });
      $item[self::PARENT_FIELD] = $this->parentId;
      $id = $this->insertRecord($item);
    });
  }

}
