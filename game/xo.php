<?php
namespace APIGame\XO;

use APIGame\SDK,
    APIGame\Core;
class XOGame implements SDK\IService
{

  protected $ai = null;
  protected $base;

  public function __construct(AI $ai = null, $base = 'XOGame')
  {
    if (session_id() == '') session_start();
    else session_regenerate_id(true);

    if (is_null($ai)) $ai = new StupidAI();
    $this->ai = $ai;
    $this->base = $base;
  }

  /**
   * Register game required routes to provided router
   *
   * @param Core\Router $router
   */
  public function registerRoutes(Core\Router $router)
  {
    $router->addRoute(new Core\RouteData([
      'verb' => 'GET',
      'path' => "^/{$this->base}/start$",
      'classname' => $this,
      'method' => 'action_start'
    ]));
    $router->addRoute(new Core\RouteData([
      'verb' => 'GET',
      'path' => "^/{$this->base}/move$",
      'classname' => $this,
      'method' => 'action_move'
    ]));
  }

  /**
   * @var array Gamefield array[0..8]
   */
  protected $field = null;
  //Cell values
  const CELL_EMPTY = ' ';
  const CELL_X = 'X';
  const CELL_O = 'O';
  //Turn results
  const TURN_ILLEGAL = -1;
  const TURN_DONE = 0;
  const TURN_XWIN = 1;
  const TURN_OWIN = 2;
  const TURN_DRAW = 3;

  /**
   * Load gamefield data if game started
   *
   * @return bool success
   */
  protected function load()
  {
    if (!isset($_SESSION[$this->base])) return false;
    $this->field = isset($_SESSION[$this->base]['field']) ? $_SESSION[$this->base]['field'] : self::getEmptyField();
    return true;
  }

  /**
   * Save gamefield data
   *
   * @return $this
   */
  protected function save()
  {
    $_SESSION[$this->base] = [
      'field' => $this->field
    ];
    return $this;
  }

  /**
   * Ends game (clear session data
   * @param mixed $res
   * @return mixed - proxied $res param
   */
  protected function endGame($res)
  {
    unset($_SESSION[$this->base]);
    return $res;
  }

  /**
   * Static class variable $checkHelper used for avoid every time indexes calculation
   * Only lines included last turned cell checked
   *
   * @return array - Lines for check for each move
   */
  protected static $checkHelper = [
    [[0, 1, 2], [0, 3, 6], [0, 4, 8]],
    [[0, 1, 2], [1, 4, 7]],
    [[0, 1, 2], [2, 5, 8], [2, 4, 6]],
    [[3, 4, 5], [0, 3, 6]],
    [[3, 4, 5], [1, 4, 7], [0, 4, 8], [2, 4, 6]],
    [[3, 4, 5], [2, 5, 8]],
    [[6, 7, 8], [0, 3, 6], [2, 4, 6]],
    [[6, 7, 8], [1, 4, 7]],
    [[6, 7, 8], [2, 5, 8], [0, 4, 8]],
  ];

  /**
   * Checks only horizontal/vertical and diagonals(if neccessary) of last turned cell
   *
   * @param string $player - CELL_X|CELL_O
   * @param int $lastpos - last move cell index(0..8)
   * @return bool - player $player wins
   */
  protected function checkWin($player, $lastpos)
  {
    $helper = self::$checkHelper[$lastpos];
    foreach ($helper as $line) {
      $win = true;
      foreach ($line as $i) {
        $win &= $this->field[$i] == $player;
      }
      if ($win) return true;
    }
    return false;
  }

  /**
   * Checks no empty fields left
   *
   * @return bool - isDrawed
   */
  protected function checkDraw()
  {
    foreach ($this->field as $cell) {
      if ($cell == self::CELL_EMPTY) return false;
    }
    return true;
  }

  /**
   * Process a turn of current player
   *
   * @param string $player - self::CELL_X, self::CELL_O
   * @param int $pos - turn cell's index
   * @return string - processing status result
   */
  protected function doTurn($player, $pos)
  {
    if ($this->field[$pos] !== self::CELL_EMPTY) return self::TURN_ILLEGAL;
    $this->field[$pos] = $player;
    if ($this->checkWin($player, $pos)) {
      $res = ($player == self::CELL_X) ? self::TURN_XWIN : self::TURN_OWIN;
      return $this->endGame($res);
    }
    if ($this->checkDraw()) return $this->endGame(self::TURN_DRAW);
    return self::TURN_DONE;
  }

  /**
   * Process AI turn
   * @return int|string - turn status result
   */
  protected function aiTurn()
  {
    $res = $this->ai->doTurn($this->field, self::CELL_O, self::CELL_X, self::CELL_EMPTY);
    if ($res === -1) return self::TURN_DRAW;
    return $this->doTurn(self::CELL_O, $res);
  }

  /**
   * Generate empty field
   * @return array
   */
  protected static function getEmptyField()
  {
    return array_fill(0, 9, self::CELL_EMPTY);
  }

  /**
   * Turn status result parsing, return API response, or 0 if game continues and need to save state
   * @param $turn
   * @return array|int
   */
  protected function parseTurnResult($turn)
  {
    switch ($turn) {
      default:
      case self::TURN_ILLEGAL:
        return ['error' => 'Illegal move'];
      case self::TURN_XWIN:
        return ['status' => 'You are win!'];
      case self::TURN_OWIN:
        return ['status' => 'You are lose!'];
      case self::TURN_DRAW:
        return ['status' => 'The game ended in a draw'];
      case self::TURN_DONE:
        return 0;
    }
  }

  /**
   * Response wrapper, adds gamefield representation to response
   * @param array $result - API response data
   * @return array - API response with additional fields
   */
  protected function wrapResponse(array $result)
  {
    $result['field'] = $this->field;
    $square = [
      implode(' ', array_slice($this->field, 0, 3)),
      implode(' ', array_slice($this->field, 3, 3)),
      implode(' ', array_slice($this->field, 6, 3)),
    ];
    $result['square'] = $square;
    return $result;
  }

  //API actions
  /**
   * Game start action. Initializes game state.
   * @param array $fv
   * @return array
   */
  public function action_start(array $fv)
  {
    $this->field = self::getEmptyField();
    $this->save();
    return $this->wrapResponse(['status' => 'Game started']);
  }

  /**
   * Game move action
   * @param array $fv
   * @return array
   */
  public function action_move(array $fv)
  {
    if ($this->load() === false)
      return ['error' => 'Game not started'];

    if (!isset($fv['pos']))
      return ['error' => 'No required pos field (0..9).'];

    $pos = (int)$fv['pos'];
    if ($pos < 0 || $pos > 9)
      return ['error' => 'Wrong pos value. Should be 0..9.'];

    //player turn
    if ($res = $this->parseTurnResult($this->doTurn(self::CELL_X, $pos)))
      return $this->wrapResponse($res);

    //AI turn
    if ($res = $this->parseTurnResult($this->aiTurn()))
      return $this->wrapResponse($res);

    //save, wait next turn
    $this->save();
    return $this->wrapResponse(['status' => 'Your turn']);
  }
}
