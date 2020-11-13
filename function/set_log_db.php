<?php

function set_log_db(array $data){
  if(!key_exists("description", $data)) throw new Exception("Descripcion no definida");
  if(!key_exists("id", $data)) $data["id"] = uniqid();

  $db = new Db(TXN_HOST,TXN_USER,TXN_PASS, TXN_DBNAME);
  $data["description"] = $db->escape_string($data["description"]);
  
  $sql = "
INSERT INTO log (" . implode(",", array_keys($data)) . ")
VALUES ('" . implode("', '", $data) . "');
";


  $db->query($sql);
  return $data["id"];
}
