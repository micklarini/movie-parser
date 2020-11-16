<?php

namespace App\base;

trait FormatterSTDOUTrait
{
  protected function formatInit() {
    print(date(\DATE_RFC822) . ": Start" . PHP_EOL);
  }
  
  protected function formatProcess(array $data) {
    print(date(\DATE_RFC822) . ": " . json_encode($data, JSON_UNESCAPED_UNICODE) . PHP_EOL);
  }

  protected function formatResult(array $data) 
  {
    print(date(\DATE_RFC822) . ": Done" . PHP_EOL);
    exit(0);
  }

}
