<?php

function get_log_db(string $id){
  $db = new Db(TXN_HOST,TXN_USER,TXN_PASS, TXN_DBNAME);
  $sql = "
SELECT * FROM log WHERE id = '{$id}';
";

  $result = $db->query($sql);
  $row = $result->fetch_assoc();
  $result->free();
  return $row;
}
