<?php

namespace App;

final class DBConfig
{
  protected static $system;
  protected static $cache;

  function __construct(\App\System $system)
  {
    self::$system = $system;
    self::$cache = [];
  }

  
  public function getVariable($name)
  {
    self::$cache = self::$cache ?? [];
    if (!isset(self::$cache[$name])) {
      $chunks = explode('.', $name);
      $sql = "SELECT * FROM `config` WHERE `name` LIKE \"{$chunks[0]}.%\"";
      $stmt = self::$system->pdo->query($sql, \PDO::FETCH_ASSOC);
      foreach ($stmt as $row) {
        self::$cache[$row['name']] = json_decode($row['value'], true, 512, JSON_BIGINT_AS_STRING | JSON_OBJECT_AS_ARRAY);
      }
    }
    return @self::$cache[$name];
  }

  function setVariable($name, $value)
  {
    self::$cache[$name] = $value;
    $sql = 'INSERT INTO `config` (`name`, `value`) VALUES(:name, :value) ON DUPLICATE KEY UPDATE `value` = :value';
    $stmt = self::$system->pdo->prepare($sql);
    $stmt->execute([':name' => $name, ':value' => json_encode($value)]);
    return $stmt->rowCount();
  }

}
