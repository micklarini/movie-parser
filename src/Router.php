<?php

namespace App;

final class Router {

  public static $routes = [];
  private static $params = [];
  public static $requestedUrl = '';
  private $passObject;

  function __construct($passObject) {
    $this->passObject = $passObject;
  }

  public static function addRoute($route, $destination = null)
  {
    if ($destination != null && !is_array($route)) {
      $route = [$route => $destination];
    }
    self::$routes = array_merge(self::$routes, $route);
  }

  public static function splitUrl($url)
  {
    return preg_split('/\//', $url, -1, PREG_SPLIT_NO_EMPTY);
  }

  public static function getCurrentUrl()
  {
    return (self::$requestedUrl ?: '/');
  }

  public function dispatch($requestedUrl)
  {
    self::$requestedUrl = $requestedUrl;

    if (isset(self::$routes[$requestedUrl])) {
      self::$params = self::splitUrl(self::$routes[$requestedUrl]);
      return self::executeAction();
    }

    foreach (self::$routes as $route => $uri) {
      if (strpos($route, ':') !== false) {
        $route = str_replace(':any', '(.+)', str_replace(':num', '([0-9]+)', $route));
      }
      if (preg_match('#^'.$route.'$#', $requestedUrl)) {
        if (strpos($uri, '$') !== false && strpos($route, '(') !== false) {
          $uri = preg_replace('#^'.$route.'$#', $uri, $requestedUrl);
        }
        self::$params = self::splitUrl($uri);
        break;
      }
    }
    return $this->executeAction();
  }

  public function executeAction()
  {
    $controller = isset(self::$params[0]) ? self::$params[0]: 'DefaultController';
    $action = isset(self::$params[1]) ? self::$params[1]: 'default_method';

    if (__NAMESPACE__ != '' && $controller{0} != '\\') {
      $controller = __NAMESPACE__ . "\\{$controller}";
    }
    else {
      $controller = substr($controller, 1);
    }

    if (class_exists($controller)) {
      $params = array_slice(self::$params, 2);
      $controller = new $controller($this->passObject);

      return call_user_func_array([$controller, $action], $params);
    }
    else {
      $params = array_slice(self::$params, 1) + [$this->passObject];
      return call_user_func_array($controller, $params);
    }
  }

}
