<?php
namespace APIGame\Core;

class Response
{
  const TYPE_JSON = 'json';
  private $type;

  /**
   * Class for http response
   * Masks all unwanted echos
   *
   * @param string $type
   */
  public function __construct($type = self::TYPE_JSON)
  {
    $this->type = $type;
    ob_start(function ($output) {
      $this->debug($output);
      return '';
    });
  }

  /**
   * Process response data
   *
   * @param array $data
   */
  public function send(array $data)
  {
    ob_end_flush();
    switch ($this->type) {
      case self::TYPE_JSON:
        $this->sendJSON($data);
        break;
    }
  }

  /**
   * JSON response implementation
   *
   * @param array $data
   */
  private function sendJSON(array $data)
  {
    $json = json_encode($data);
    header('Content-Type: application/json');
    header('Content-Size: ' . strlen($json));
    echo $json;
    exit();
  }

  /**
   * Callback to store all undirect output to a file(last request only)
   *
   * @param $data
   */
  private function debug($data)
  {
    file_put_contents('debug.log', $data);
  }
}
