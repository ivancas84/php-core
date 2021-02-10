<?php

function add_description_log_db(string $id, string $description){
  $db = new Db(TXN_HOST,TXN_USER,TXN_PASS, TXN_DBNAME);
  $description = $db->escape_string($description);
  
  $sql = "
UPDATE log SET description = concat(description, '" . $description . "')
WHERE id = '" . $id . "';
";

  $db->query($sql);
  return $id;
}
