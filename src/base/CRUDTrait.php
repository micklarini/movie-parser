<?php

namespace App\base;

trait CRUDTrait
{
  public static function prepareParams($fields) {
    $keys = array_keys($fields);
    return array_combine(
      array_map(function($item) { return ":{$item}"; }, $keys),
      array_values($fields)
    );
  }
  
  public static function selectOneByFields(array $fields, string $cop = 'AND', \PDO $pdo = null)
  {
    $pdo = $pdo ?? self::$system->pdo;
    $keys = array_keys($fields);
    $condition = array_map(function($name) { return "`{$name}` = :{$name}"; }, $keys);
    $sql = "SELECT * FROM `" . self::TABLE_NAME . '`' .
      (!empty($condition) ? (' WHERE ' . implode(" {$cop} ", $condition)) : '');

    $stmt = $pdo->prepare($sql);
    $stmt->execute(self::prepareParams($fields));
    $row = $stmt->fetch(\PDO::FETCH_ASSOC);
    return $row;
  }

  public function insert(array $record, \PDO $pdo = null)
  {
    $pdo = $pdo ?? self::$system->pdo;
    $keys = array_keys($record);
    $sql = "INSERT INTO `" . self::TABLE_NAME . "` (" .
      implode(', ', array_map(function($item) {
        return "`{$item}`";
      }, $keys)) .
      ') VALUES (' .
      implode(', ', array_map(function($item) {
        return ":{$item}";
      }, $keys)) . ') ' .
      'ON DUPLICATE KEY UPDATE ' .
      implode(', ', array_map(function($key, $value) {
        return "`{$key}` = :{$value}";
      }, $keys, $keys));
    $stmt = $pdo->prepare($sql);
    $stmt->execute($this->prepareParams($record));
    return $pdo->lastInsertId();
  }

  public function delete($id, \PDO $pdo = null)
  {
    $pdo = $pdo ?? self::$system->pdo;

    $sql = "DELETE FROM `" . self::TABLE_NAME . "` WHERE ` " . self::ID_FIELD . "` = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    return $stmt->rowCount();
  }

  public function update(array $record, \PDO $pdo = null)
  {
    $pdo = $pdo ?? self::$system->pdo;

    $keys = array_keys($record);
    $sql = "UPDATE `" . self::TABLE_NAME . "` SET " .
      implode(', ', array_map(function($key, $value) {
        return "`{$key}` = :{$value}";
      }, $keys, $keys)) .
      ' WHERE `' . self::ID_FIELD . '` = :' . self::ID_FIELD;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($this->prepareParams($record));
    return $stmt->rowCount();
  }

}
