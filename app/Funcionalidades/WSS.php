<?php

namespace App\Funcionalidades;

/**
* Inicio del servicio de AFIP.
*/
class WSS{

  public static function cert($production = false){
    $cert = 'certificados/23350480269PEM';
    if($production){
        //$cert = 'certificados/facturar1_56e8de30d2d8aefc.crt';
        $cert = 'certificados/facturacion-1_33f9b37080174e86.crt';//CIRO
    }
    /*
    return [
      'CUIT' => 23350480269,
      'production' => $production,
      'cert' => $cert,
      'key' => 'certificados/23350480269',
      'passphrase' => '23350480269',
    ];
    */
    return [
      'CUIT' => 21133922181,
      'production' => $production,
      'cert' => $cert,
      'key' => 'certificados/21133922181',
      'passphrase' => '21133922181',
    ];
  }
}
