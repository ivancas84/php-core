<?php

class AuthJwt {

  public $jwt = null;
  public $valid = false;

  public function readJwt() {
    $authHeader = array_key_exists('Authorization', apache_request_headers()) ?  apache_request_headers()['Authorization'] : null;
    list($this->jwt) = sscanf( $authHeader, 'Authorization: Bearer %s');
  }

  protected function aud() {
      $aud = '';

      if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
          $aud = $_SERVER['HTTP_CLIENT_IP'];
      } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
          $aud = $_SERVER['HTTP_X_FORWARDED_FOR'];
      } else {
          $aud = $_SERVER['REMOTE_ADDR'];
      }

      $aud .= @$_SERVER['HTTP_USER_AGENT'];

      return sha1($aud);
  }

  protected function iss() {
    $iss = '';
    if (!empty($_SERVER['SERVER_NAME'])) $iss = $_SERVER['SERVER_NAME'];
    if (!empty($_SERVER['REQUEST_URI'])) $iss .= $_SERVER['REQUEST_URI'];

    return sha1($iss);
  }
  
  public function validateJwt($options = []) {
    $this->valid = false;

    if(empty($this->jwt)) throw new Exception("JWT is empty");
    
    $token = JWT::decode($this->jwt, $key, array('HS256'));

    if(in_array("aud", $options)){
      if($token->aud !== aud()) throw new Exception("Invalid user logged in.");
    }

    if(in_array("iss", $options)){
      if($token->iss !== iss()) throw new Exception("Invalid user logged in.");
    }

    $this->valid = true;
  }

}