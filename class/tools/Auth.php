<?php



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
  
  public function authenticate($options = []) {
    $authHeader = array_key_exists('Authorization', apache_request_headers()) ?  apache_request_headers()['Authorization'] : null;
    list($jwt) = sscanf( $authHeader, 'Authorization: Bearer %s');
    if(!$jwt) throw new Exception("No tiene permisos para acceder al recurso solicitado", "401");
    
    $token = JWT::decode($jwt, $key, array('HS256'));

    if(in_array("aud", $options)){
      if($token->aud !== aud()) throw new Exception("Error al verificar token", 401);
    }

    if(in_array("iss", $options)){
      if($token->iss !== iss()) throw new Exception("Error al verificar token", 401);
    }

    return $token;
  }

  
  public function authorize($entityName, $permission, $options = ["aud"]){
    require_once("function/public_scope.php");
    if(array_key_exists($permission, public_scope())
      && in_array($entityName, public_scope()[$permission])) return true;
    
    $token = $this->authenticate($options);

    require_once("function/private_scope.php");
    if(array_key_exists($permission, private_scope())
      && in_array($entityName, private_scope()[$permission])) return true;

    if(array_key_exists($permission, $token->scope)
      && in_array($entityName, $token->scope[$permission])) return true;

    throw new Exception("Usuario no autorizado");
  }

}