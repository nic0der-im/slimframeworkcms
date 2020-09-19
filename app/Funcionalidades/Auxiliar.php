<?php

namespace App\Funcionalidades;

/**
*/
class Auxiliar{

  public static function money_to_float($str){
    return str_replace(",",".",preg_replace('/\$|\ |\./','',$str));
  }
}
