<?php

require_once("class/Transaction.php");
    
try {
  $transaction = new Transaction();
  $id = $transaction->begin();
  echo $id;
} catch (Exception $ex) {
  Controller::httpResponseCodeException(500, $ex);
}