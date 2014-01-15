<?php
namespace Tests\Core;

use APIGame\Core;

require_once __DIR__ . "/../../core/router.php";

class Router extends \PHPUnit_Framework_TestCase
{
  protected function setUp()
  {
  }

  public function action($data)
  {
    return $data;
  }

  public function test_success()
  {
    $router = Core\Router::getInstance();
    $router->addRoute(new Core\RouteData([
      'verb' => 'GET',
      'path' => "^/testuri\d+$",
      'classname' => $this,
      'method' => 'action'
    ]));
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = '/testuri25?somequery=5';
    $_GET = ['somequery' => 5];
    $this->assertEquals($router->execURI()['somequery'], 5);
  }

  public function test_fails()
  {
    $router = Core\Router::getInstance();
    $router->addRoute(new Core\RouteData([
      'verb' => 'GET',
      'path' => "^/testuri\d+$",
      'classname' => $this,
      'method' => 'wrong'
    ]));
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = '/test?somequery=5';
    $_GET = ['somequery' => 5];
    try {
      $router->execURI();
      $this->assertTrue(false, "Should thrown RouterException");
    } catch (Core\RouterException $e) {
      $this->assertEquals($e->getMessage(), "Unknown request");
    }
    $_SERVER['REQUEST_URI'] = '/testuri1';
    try {
      $router->execURI();
      $this->assertTrue(false, "Should thrown RouterException");
    } catch (Core\RouterException $e) {
      $this->assertEquals($e->getMessage(), "Method " . get_class() . "->wrong not Exists");
    }

  }

  protected function tearDown()
  {
  }
}

?>
