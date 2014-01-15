<?php
namespace Tests\Core;

use APIGame\Core;

require_once __DIR__ . "/../../core/loader.php";

class Loader extends \PHPUnit_Framework_TestCase
{
  protected function setUp()
  {
    Core\Loader::getInstance();
  }

  public function test_singleton()
  {
    $loader1 = Core\Loader::getInstance();
    $loader2 = Core\Loader::getInstance();
    $this->assertTrue($loader1 === $loader2);
  }

  public function test_simple_class()
  {
    $a = new Loader\A();
    $this->assertEquals($a->foo(), 'A');
    $b = new Loader\B();
    $this->assertEquals($b->foo(), 'B');
  }

  public function test_not_found()
  {
    try {
      new Core\Abracadabra();
      $this->assertTrue(false, 'Should be exception thrown');
    } catch (Core\LoaderException $e) {
      $this->assertTrue(true);
    }
  }

  protected function tearDown()
  {
  }
}

?>
