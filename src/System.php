<?php

namespace App;

final class System
{
  public static $routes = [
    '/' => '\App\MovieController/index',
    '/info/:any' => '\App\MovieController/info',
    '/parse' => '\App\ParserHTMLController/parseContinue',
    '/parse/top' => '\App\ParserHTMLController/parseTop',
    '/parse/top_tv/:num' => '\App\ParserHTMLController/parseTopSeries/$1',
    '/parse/:num/:any' => '\App\ParserHTMLController/parseList/$1/$2',
    '/rating/:num' => '\App\MovieController/rating/$1',
    '/rating/:num/:num' => '\App\MovieController/rating/$1/$2',
    '/api/rating/:num' => '\App\MovieController/apiRating/$1',
    '/api/rating/:num/:num' => '\App\MovieController/apiRating/$1/$2',
    '/movie/:num' => '\App\MovieController/movie/$1',
    '/api/movie/:num' => '\App\MovieController/apiMovie/$1',
  ];

  public static $cliRoutes = [
    '/' => '\App\HelpController/index',
    '/parse' => '\App\ParserCliController/parseContinue',
    '/parse/top' => '\App\ParserCliController/parseTop',
    '/parse/top_tv/:num' => '\App\ParserCliController/parseTopSeries/$1',
    '/parse/:num/:any' => '\App\ParserCliController/parseList/$1/$2',
  ];

  public $conf;
  public $vocs;
  public $path;

  private $router;
  protected $_pdo;
  protected $_dbconf;

  function __construct()
  {
    $this->initConfig();
    $this->vocs = new \stdClass();
  }

  public function __get($name)
  {
    $pName = "_{$name}";
    $pCall = 'init' . ucfirst($name);
    if (property_exists($this, $pName) && is_null($this->$pName)) {
      return $this->$pCall();
    }
    else {
      return $this->$pName;
    }
  }

  public function startUp($cli, $path)
  {
    $this->conf->cli = $cli;
    $this->path = $path;
    $this->router = new Router($this);
    $this->router::addRoute($cli ? self::$cliRoutes : self::$routes);
    $this->router->dispatch($this->path);
  }

  protected function initConfig()
  {
    $this->conf = new \stdClass();

    $pattern = __DIR__ . '/../conf' . '/*.{ini,json}';
    $files = glob($pattern, GLOB_BRACE);
    foreach ($files as $entry) {
      $fileName = realpath($entry);
      $fileInfo = pathinfo($fileName);
      $fileContents = file_get_contents($fileName);
      switch ($fileInfo['extension']) {
        case 'ini':
          $data = parse_ini_string($fileContents, true, INI_SCANNER_TYPED);
          break;
        case 'json':
          $data = json_decode($fileContents, true);
          break;
      }
      $this->conf->{$fileInfo['filename']} = (object) $data;
    }
    return $this->conf;
  }

  protected function initPdo()
  {
    $dc = &$this->conf->db->connection;
    $options = array_merge($dc['options']);
    try {
      $this->_pdo = new \PDO($dc['dsn'], $dc['username'], $dc['passwd'], $options);
    }
    catch (\Exception $e) {
      die('Database connection error');
    }
    return $this->_pdo;
  }

  protected function initDbconf()
  {
    $this->_dbconf = new DBConfig($this);
    return $this->_dbconf;
  }

}
