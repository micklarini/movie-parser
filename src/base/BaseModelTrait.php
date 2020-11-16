<?php

namespace App\base;

trait BaseModelTrait
{
  use \App\base\CRUDTrait;

  function __isset($name)
  {
    return array_key_exists($name, $this->_fields);
  }

  function __unset($name)
  {
    unset($this->_fields[$name]);
  }

  function __get($name)
  {
    return @$this->_fields[$name];
  }

  function __set($name, $value)
  {
    $this->_fields[$name] = $value;
  }

  public static function locate(array $fields, \App\System $system)
  {
    $data = self::selectOneByFields([self::LOCATE_FIELD => $fields[self::LOCATE_PROP]], 'AND', $system->pdo);
    if (empty($data) || !$data[self::ID_FIELD]) {
      return null;
    }
    $instanceOf = __CLASS__;
    $instance = new $instanceOf($system);
    $instance->_fields = $data;
    return $instance;
  }

  public static function load($id, \App\System $system)
  {
    $data = self::selectOneByFields([self::ID_FIELD => $id], 'AND', $system->pdo);
    if (empty($data) || !$data[self::ID_FIELD]) {
      return null;
    }
    $instanceOf = __CLASS__;
    $instance = new $instanceOf($system);
    $instance->_fields = $data;

    foreach (self::DEP_FIELDS as $field => $entry) {
      $dependent = new $entry($system, $instance->id);
      $instance->_fields[$field] = $dependent->list();
    }
    foreach (self::VOC_FIELDS as $field => $entry) {
      if (isset($instance->_fields[$entry['source']])) {
        $instance->_fields[$field] = 
          self::$system->vocs->{$entry['vocabulary']}->getIndexEntry($instance->_fields[$entry['source']]);
      }
    }
    foreach (self::CALC_FIELDS as $field => $entry) {
      $instance->_fields[$field] = $instance->$entry();
    }

    return $instance;
  }

  public function apply(array $source)
  {
    $directFields = $errors = [];
    $stored = $this->id;

    foreach (self::MAP_CREATION as $name => $op) {
      if (is_string($op)) {
        $directFields[$op] = @$source[$name];
      }
      elseif (is_array($op) && !empty($op)) {
        switch ($op[0]) {
          case 'direct':
            $directFields[$op[1]] = self::{$op[2]}($op[3], $source[$name]);
            break;
          case 'depend':
            $this->delayed[$name] = [
              'method' => $op[1],
              'args' => array_merge(array_slice($op, 2), [$source[$name]]),
            ];
            break;
        }
      }
    }
    $this->_fields = $directFields;
    if ($stored) {
      $this->_fields[self::ID_FIELD] = $stored;
      $this->processDelayed();
    }
    return $this;
  }

  public function save() {
    $this->update($this->_fields);
    return $this;
  }

  public static function createFrom(array $source, \App\System $system)
  {
    $instanceOf = __CLASS__;
    $instance = new $instanceOf($system);
    $instance->apply($source);
    $instance->_fields[self::ID_FIELD] = $instance->insert($instance->_fields);
    $instance->processDelayed();
    return $instance;
  }

  protected function processDelayed() {
    $fields = &$this->_fields;
    array_walk($this->delayed, function($item, $field) use ($fields) {
      $fields[$field] = $this->{$item['method']}(...$item['args']);
    });
    $this->delayed = [];
  }

  public function checkVocExists(string $name, string $className)
  {
    if (!is_object(@self::$system->vocs->$name)) {
      self::$system->vocs->$name = new $className(self::$system);
    }
    return self::$system->vocs->$name;
  }

  public static function getVocEntryId(string $name, string $value)
  {
    $voc = &self::$system->vocs->$name;
    return $voc->find($value) ?? $voc->insert($value);
  }

  public function makeDependentList(string $depName, $filter, $values)
  {
    $dep = new $depName(self::$system, $this->id);
    $dep->appendReplace($values, $filter);
  }

}
