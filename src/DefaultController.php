<?php

namespace App;

use \App\base\TwigController;

final class DefaultController extends TwigController
{
  function __construct(\App\System $system)
  {
    parent::__construct($system);
  }
  
  function default_method() {
    if (!self::$system->conf->cli) {
      header("HTTP/1.0 404 Not Found");
    }
    return "";
  }

}
