<?php
namespace APIGame;

use APIGame\Core,
  APIGame\SDK;

class API
{
  private $start;
  private $router = null;
  private $response = null;

  public function __construct()
  {
    $this->start = microtime(1);
    $this->router = Core\Router::getInstance();
    $this->response = new Core\Response();
  }

  /**
   * Register service routing to API
   *
   * @param SDK\IService $svc
   * @return $this
   */
  public function addService(SDK\IService $svc)
  {
    $svc->registerRoutes($this->router);
    return $this;
  }

  /**
   * Process API request
   *
   */
  public function run()
  {
    try {
      $res = $this->router->execURI();
      $res['time'] = microtime(1) - $this->start;
      $this->response->send($res);
    } catch (Core\RouterException $e) {
      $this->response->send(["error" => $e->getMessage()]);
    } catch (\Exception $e) {
      echo $e->getMessage();
    }
  }
}
