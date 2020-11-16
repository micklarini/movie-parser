<?php

namespace App\base;

trait FormatterJSONTrait
{
  
  protected function formatInit() {
    header('Content-Type: application/json; charset=UTF-8', true, 200);
  }
  
  protected function formatResult(array $data) 
  {
    exit(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
  }

}
