<?php
namespace APIGame\SDK;

use APIGame\Core;

interface IService
{
  public function registerRoutes(Core\Router $router);
}
