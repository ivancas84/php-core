<?php

function rest($url, $entity_name, $api, array $display = []){
  /**
   * $display = [
   *   "fields" => [ "per-nombres", "per-apellidos" ],
   *   "size" => 0
   * ];
   * 
   * $data = rest("http://localhost/fines2-temp/api/", "nomina2", "advanced", $display);
   */
  $curl = curl_init();

  curl_setopt($curl, CURLOPT_POST, 1);
  curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($display));
  curl_setopt($curl, CURLOPT_URL, $url . "/" . $entity_name . "/" . $api);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
  $result = curl_exec($curl);
  if($result === false) throw new Exception("Error al acceder a " . $url);
  return $result;
}
