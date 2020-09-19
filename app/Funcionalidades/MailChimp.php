<?php

namespace App\Funcionalidades;

/**
* Contiene las funciones para enviar un correo.
*/
class MailChimp{

        public static function api(){
        return new \GuzzleHttp\Client([
            'headers' => [ 
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic 9b401647fd29d34877505b56eb870210-us18',
                 ],
            'base_uri' => 'https://us18.api.mailchimp.com/3.0/',
            'timeout'  => 15.0,
          ]);
        }
}