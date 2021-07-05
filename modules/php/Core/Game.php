<?php
namespace NUMDROP\Core;
use numberdrop;

/*
 * Game: a wrapper over table object to allow more generic modules
 */
class Game
{
  public static function get()
  {
    return numberdrop::get();
  }
}
