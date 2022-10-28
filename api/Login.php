<?php

class LoginApi {
  /**
   * Comportamiento general de login
   * Por defecto accede al metodo user_scope que tiene las siguientes caractertisticas
   * [
   *   "user1" => ["password" => "password", "scope" => ["read"=>"read","write"=>"write",...]]
   *   "user2" => ["password" => "password", "scope" => ["read"=>"read","write"=>"write",...]]
   * ]
   */

  public $entityName;
  public $container;
  public $permission = "r";

  public function main(){
    $data = $this->filterData();
    $jwt = $this->login($data);
    return ["jwt"=>$jwt];
  }

  protected function filterData(){
    $data=file_get_contents("php://input");
    return json_decode($data, true);
    if(empty($data) || empty($data["user"]) || empty($data["password"])) throw new Exception("Datos de login no validos", 400);
    return $data;
  }

  protected function login(array $data){
    require_once($_SERVER["DOCUMENT_ROOT"] . "/" . PATH_CONFIG . "/user_scope.php");
    if(!array_key_exists($data["user"],user_scope()) || user_scope()[$data["user"]]["password"] != $data["password"]) throw new Exception("Usuario no autorizado", 401);
    return $this->container->getAuth()->login($data["user"]);
  }
}



