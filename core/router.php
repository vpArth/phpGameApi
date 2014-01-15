<?php
namespace APIGame\Core;

/**
 * Class RouteData
 * Container for route information for Router::addRoute method
 *
 * @package APIGame\Core
 */
class RouteData
{
  public function __construct($data)
  {
    $this->verb = isset($data['verb']) ? $data['verb'] : 'GET';
    $this->path = isset($data['path']) ? $data['path'] : '^/$';
    $this->classname = $data['classname'];
    $this->method = $data['method'];
  }

  public $verb;
  public $path;
  public $classname;
  public $method;
}

class RouterException extends \Exception
{
}

interface IRouter
{
  public function addRoute(RouteData $data);

  public function execURI();
}

class Router implements IRouter
{
  private static $instance = null;

  private function __clone()
  {
  }

  private function __construct()
  {
  }

  public static function getInstance()
  {
    return self::$instance ? : (self::$instance = new self());
  }

  private $routes = [];

  /**
   * Register routing rule
   *
   * @param RouteData $data
   * @return $this
   */
  public function addRoute(RouteData $data)
  {
    $this->routes[$data->verb . ' ' . $data->path] = ['classname' => $data->classname, 'method' => $data->method];
    return $this;
  }

  /**
   * Wrapper for user data
   *
   * @return array
   */
  protected function getParams()
  {
    $fv = array_merge($_GET, $_POST, $_FILES);
    //here can be some filters, modifications
    unset($_GET, $_POST, $_FILES, $_REQUEST);
    return $fv;
  }

  /**
   * Process request
   *
   * @return mixed
   * @throws RouterException
   */
  public function execURI()
  {
    foreach ($this->routes as $pattern => $data) {

      list($method, $path) = explode(' ', $pattern, 2);
      list($url) = explode('?', $_SERVER['REQUEST_URI'], 2);

      if ($method === $_SERVER['REQUEST_METHOD'] && preg_match('#' . $path . '#i', $url)) {
        $class = is_object($data['classname']) ? $data['classname'] : new $data['classname']();
        if (!method_exists($class, $data['method'])) {
          $classname = is_object($data['classname']) ? get_class($data['classname']) : $data['classname'];
          throw new RouterException("Method {$classname}->{$data['method']} not Exists");
        }
        return $class->{$data['method']}($this->getParams());
      }
    }
    throw new RouterException("Unknown request");
  }
}
