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
       "exp" => time() + (60 * 60 * 24),
       "user" => $user,
       "scope" => user_scope()[$user]["scope"],
       "view" => user_scope()[$user]["view"]
    ];
    $jwt = JWT::encode($payload, JWT_KEY);
    //$token = JWT::decode($jwt, JWT_KEY, ['HS256']);
    return $jwt;
  }

  protected function getAuthorizationHeader(){
    $headers = null;
    if (isset($_SERVER['Authorization'])) {
        $headers = trim($_SERVER["Authorization"]);
    }
    else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx or fast CGI
        $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
    } elseif (function_exists('apache_request_headers')) {
        $requestHeaders = apache_request_headers();
        // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
        $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
        //print_r($requestHeaders);
        if (isset($requestHeaders['Authorization'])) {
            $headers = trim($requestHeaders['Authorization']);
        }
    }
    return $headers;
  }
  
  protected function getBearerToken() {
    $headers = $this->getAuthorizationHeader();
    // HEADER: Get the access token from the header
    if (!empty($headers)) {
      if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
          return $matches[1];
      }
    }
    return null;
  }


  public function payload(){
    $jwt = $this->getBearerToken();
    //$jwt = filter_input(INPUT_GET, 'jwt', FILTER_SANITIZE_SPECIAL_CHARS);
    if(empty($jwt)) throw new Exception("Usuario no autorizado", 401);
    return JWT::decode($jwt, JWT_KEY, ['HS256']);
  }

  public function authenticate() {
    $payload = $this->payload();
    
    if(isset($payload->aud)){
      if($payload->aud !== $this->aud()) throw new Exception("Usuario no autorizado", 401);
    }

    if(isset($payload->iss)){
      if($payload->iss !== $this->iss()) throw new Exception("Usuario no autorizado", 401);
    }

    return $payload;
  }

  protected function authorizePermissions($entity_name, $permissions, $scope){
    $authorized = false;
    foreach($scope as $sc){
      $s = explode(".", $sc);
      if($s[0] == $entity_name){
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

  public function authorize($entity_name, $permissions){
    require_once($_SERVER["DOCUMENT_ROOT"] . "/" . PATH_CONFIG . "/public_scope.php");
    if($this->authorizePermissions($entity_name, $permissions, public_scope())) return true;
    $token = $this->authenticate();
    require_once($_SERVER["DOCUMENT_ROOT"] . "/" . PATH_CONFIG . "/private_scope.php");
    if($this->authorizePermissions($entity_name, $permissions, private_scope())) return true;
    if($this->authorizePermissions($entity_name, $permissions, $token->scope)) return true;
    throw new Exception("Usuario no autorizado");
  }

}