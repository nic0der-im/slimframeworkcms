<?php

namespace App\Funcionalidades;

/**
*
*/
class OneSignal{

        //app_id 78034361-ab14-4018-a430-6be0744c770a

        public static function api(){
        return new \GuzzleHttp\Client([
            'headers' => [
              'Content-Type' => 'application/json; charset=utf-8',
              'Authorization' => 'Basic YmMxYzI4MjctZGE0Ny00OTQ3LTg5MTMtZDg5YzQxOTZlZGRi',
             ],
            'base_uri' => 'https://onesignal.com/api/v1/',
            'timeout'  => 15.0,
          ]);
        }
}
