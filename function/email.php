<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Load Composer's autoloader

require $_SERVER["DOCUMENT_ROOT"] . "/" . PATH_ROOT . '/vendor/autoload.php';

function email($addresses, $subject, $body){
  /**
   * Para utilizar esta funcion, debe utilizar https://github.com/PHPMailer/PHPMailer v6.1.7 
   * y configurar las constantes correspondientes
   */
  $mail = new PHPMailer(true);

  //Server settings
  $mail->SMTPDebug = SMTP::DEBUG_SERVER;                      // Enable verbose debug output
  $mail->isSMTP();                                            // Send using SMTP
  $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
  $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;         // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` encouraged
  $mail->Port       = 587;                                    // TCP port to connect to, use 465 for `PHPMailer::ENCRYPTION_SMTPS` above
  $mail->CharSet = 'UTF-8';

  $mail->Host       = "mail.planfines2.com.ar";                    // Set the SMTP server to send through
  $mail->Username   = "docentes@planfines2.com.ar";                     // SMTP username
  $mail->Password   = "Educacion2021";                               // SMTP password
  

  //Recipients
  $mail->setFrom("docentes@planfines2.com.ar", "Docentes CENS 462");
  $mail->AddAddress("ivancas84@gmail.com", "Ivan Castañeda");
  echo "voy a enviar";
  //foreach($addresses as $email => $name) $mail->AddAddress($email, $name);

  // Content
  $mail->isHTML(true);                                  // Set email format to HTML
  $mail->Subject = $subject;
  $mail->Body    = $body;

  $mail->send();
  

  }