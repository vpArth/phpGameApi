<?php
namespace APIGame\XO;
interface AI
{
  public function doTurn($field, $selfMark, $enemyMark, $emptyMark);
}

/**
 * XO Game AI - Make turns to first empty field
 * Class StupidAI
 * @package APIGame\XO
 */
class StupidAI implements AI
{
  public function doTurn($field, $selfMark, $enemyMark, $emptyMark)
  {
    for ($i = 0; $i < 9; $i++) {
      if ($field[$i] === $emptyMark) {
        return $i;
      }
    }
    return -1;
  }
}

/**
 * XO Game AI - Make turns to random empty field
 * Class RandomAI
 * @package APIGame\XO
 */
class RandomAI implements AI
{
  public function doTurn($field, $selfMark, $enemyMark, $emptyMark)
  {
    $empties = [];
    for ($i = 0; $i < 9; $i++)
      if ($field[$i] === $emptyMark)
        $empties[] = $i;
    if (!count($empties)) return -1;
    shuffle($empties);
    return $empties[0];
  }
}
