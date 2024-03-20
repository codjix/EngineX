<?php

namespace HimaPro;

class Router {

  private static $appPath = "";
  private static $groupPrefex = "";
  public static $routes = array();
  public static $guards = array();

  public static function init(string $appPath = null) {
    $scriptPath = explode("/", $_SERVER["SCRIPT_NAME"]);
    array_pop($scriptPath);
    $scriptPath = str_replace($_SERVER["DOCUMENT_ROOT"], "", implode("/", $scriptPath));
    $appPath = $appPath ?? $scriptPath;
    self::$appPath = $appPath;
    return new static;
  }

  public static function any(string $path, callable $callback) {
    $path = self::$appPath . self::$groupPrefex . $path;
    self::$routes['GET'][$path] = $callback;
    self::$routes['POST'][$path] = $callback;
    self::$routes['PUT'][$path] = $callback;
    self::$routes['DELETE'][$path] = $callback;
    self::$routes['PATCH'][$path] = $callback;
    self::$routes['HEAD'][$path] = $callback;
    return new static;
  }

  public static function get(string $path, callable $callback) {
    $path = self::$appPath . self::$groupPrefex . $path;
    self::$routes['GET'][$path] = $callback;
    return new static;
  }

  public static function post(string $path, callable $callback) {
    $path = self::$appPath . self::$groupPrefex . $path;
    self::$routes['POST'][$path] = $callback;
    return new static;
  }

  public static function put(string $path, callable $callback) {
    $path = self::$appPath . self::$groupPrefex . $path;
    self::$routes['PUT'][$path] = $callback;
    return new static;
  }

  public static function delete(string $path, callable $callback) {
    $path = self::$appPath . self::$groupPrefex . $path;
    self::$routes['DELETE'][$path] = $callback;
    return new static;
  }
  
  public static function patch(string $path, callable $callback) {
    $path = self::$appPath . self::$groupPrefex . $path;
    self::$routes['PATCH'][$path] = $callback;
    return new static;
  }
  
  public static function head(string $path, callable $callback) {
    $path = self::$appPath . self::$groupPrefex . $path;
    self::$routes['HEAD'][$path] = $callback;
    return new static;
  }
  
  public static function group(string $groupPrefex, callable $callback) {
    self::$groupPrefex = $groupPrefex;
    call_user_func_array($callback, array());
    self::$groupPrefex = "";
    return new static;
  }
  
  public static function guard(string $name, callable $callback){
    self::$guards[$name] = $callback;
    return new static;
  }
  
  public static function withGuard(string $name, $props = null){
    $passed = call_user_func(self::$guards[$name], $props);
    if ($passed) {
      return new static;
    } else {
      return $passed;
    }
  }

  public static function run() {
    $request_path = explode("?", $_SERVER["REQUEST_URI"])[0];
    $request_method = $_SERVER['REQUEST_METHOD'];
    foreach (self::$routes[$request_method] as $route_path => $callback) {
      if ($request_path == self::$appPath) {
        call_user_func_array($callback, array());
        exit;
      }
      $pattern = str_replace('/', '\/', $route_path);
      $pattern = preg_replace('/({\w+})/', '([^\/]+)', $pattern);
      $pattern = '/^' . $pattern . '$/';
      if (preg_match($pattern, $request_path, $params)) {
        array_shift($params);
        call_user_func_array($callback, $params);
        exit();
      }
    }
    if ($_SERVER["REQUEST_URI"]) {
      if (self::$routes[$request_method][self::$appPath . "/404"]) {
        call_user_func_array(self::$routes[$request_method][self::$appPath . "/404"], array());
      } else {
        echo "404 not found";
        http_response_code(404);
      }
    }
  }
}
