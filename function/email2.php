<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Load Composer's autoloader

require $_SERVER["DOCUMENT_ROOT"] . "/" . PATH_ROOT . '/vendor/autoload.php';

function email2($host, $user, $password, $fromAdress, $fromName, $addresses, $subject, $body){
  /**
   * Para utilizar esta funcion, debe utilizar https://github.com/PHPMailer/PHPMailer v6.1.7 
   * y configurar las constantes correspondientes
   */
  $mail = new PHPMailer(true);

  //Server settings
  //$mail->SMTPDebug = SMTP::DEBUG_SERVER;                      // Enable verbose debug output
  $mail->isSMTP();                                            // Send using SMTP
  $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
  $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;         // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` encouraged
  $mail->Port       = 587;                                    // TCP port to connect to, use 465 for `PHPMailer::ENCRYPTION_SMTPS` above
  $mail->CharSet = 'UTF-8';

  $mail->Host       = $host;                    // Set the SMTP server to send through
  $mail->Username   = $user;                     // SMTP username
  $mail->Password   = $password;                               // SMTP password
  

  //Recipients
  $mail->setFrom($fromAdress, $fromName);
  foreach($addresses as $email => $name) $mail->AddAddress($email, $name);

  // Content
  $mail->isHTML(true);                                  // Set email format to HTML
  $mail->Subject = $subject;
  $mail->Body    = $body;

  $mail->send();
  

  }