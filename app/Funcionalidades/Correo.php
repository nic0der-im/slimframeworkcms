<?php

namespace App\Funcionalidades;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PhpImap\Mailbox;
/**
* Contiene las funciones para enviar un correo.
*/
class Correo{

        public static function ciro(){
        	$mail = new PHPMailer(true);
        	$mail->SMTPDebug = 2;                                 // Enable verbose debug output
                //$mail->isSMTP();                                      // Descomentar solo en LOCAL
                $mail->Host = 'smtp.gmail.com'; 
                $mail->SMTPAuth = true;                               // Enable SMTP authentication
                $mail->Username = 'ciro.noreply@gmail.com';                 // SMTP username
                $mail->Password = 'k1r8BTW2T2';                           // SMTP password
                $mail->SMTPSecure = 'ssl';                            // Enable TLS encryption, `ssl` also accepted
                $mail->Port = 465;                                    // TCP port to connect to
                $mail->CharSet = "UTF-8";
        return $mail;
        }

}