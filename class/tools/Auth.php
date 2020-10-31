<?php

use Firebase\JWT\JWT;
require_once $_SERVER["DOCUMENT_ROOT"] . "/" . PATH_ROOT . '/vendor/autoload.php';


class Auth {

  public $jwt = null;
  public $valid = false;


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
  
  public function login($user){

    $payload = [
       "aud" => $this->aud(),
       "iat" => time(),
       "exp" => time() + (60 * 60),
       "user" => $user,
       "scope" => user_scope()[$user]["scope"]
    ];
    $jwt = JWT::encode($payload, JWT_KEY);
    //$token = JWT::decode($jwt, JWT_KEY, ['HS256']);
    return $jwt;
  }

  public function authenticate() {
    $jwt = filter_input(INPUT_GET, 'jwt', FILTER_SANITIZE_SPECIAL_CHARS);
    if(empty($jwt)) throw new Exception("Usuario no autorizado", 401);
    $payload = JWT::decode($jwt, JWT_KEY, ['HS256']);
    
    if(isset($payload->aud)){
      if($payload->aud !== $this->aud()) throw new Exception("Usuario no autorizado", 401);
    }

    if(isset($payload->iss)){
      if($payload->iss !== $this->iss()) throw new Exception("Usuario no autorizado", 401);
    }

    return $payload;
  }

  protected function authorizePermissions($entityName, $permissions, $scope){
    $authorized = false;
    foreach($scope as $sc){
      $s = explode(".", $sc);
      if($s[0] == $entityName){
        $authorized = true;
        foreach(str_split($permissions) as $p_){
          if(strpos($s[1], $p_) === false) {
            $authorized = false;
            break;
          }
        }
        if(!$authorized) break;
      }
    }
    if($authorized) return true;
  }

  public function authorize($entityName, $permissions, $options = ["aud"]){
    require_once($_SERVER["DOCUMENT_ROOT"] . "/" . PATH_CONFIG . "/public_scope.php");
    if($this->authorizePermissions($entityName, $permissions, public_scope())) return true;
    $token = $this->authenticate($options);
/*
    require_once($_SERVER["DOCUMENT_ROOT"] . "/" . PATH_CONFIG . "/private_scope.php");
    if($this->authorizePermissions($entityName, $permissions, private_scope())) return true;
    if($this->authorizePermissions($entityName, $permissions, $token->scope)) return true;
    throw new Exception("Usuario no autorizado");*/
  }

}