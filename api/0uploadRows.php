<?php

require_once("class/Controller.php");
require_once("class/Transaction.php");

$entity = $this->filter->requestRequired("entity");
$transactionId = $this->filter->postRequired("transaction");
$rows = $this->filter->postArrayRequired("rows");
$params = $this->filter->postArrayRequired("params");

try {
  Controller::entityDao($entity);
  
  $render = array();
  foreach($params as $fieldName => $fieldValue) array_push($render, ["field" => $fieldName, 'value' => $fieldValue]);

  $ids = $entityDao->ids($render);

  //recorrer datos para persistir
  $transaction = new Transaction($transactionId);
  $idsReturn = array(); //claves persisitidas a retornar
  for($i = 0; $i < count($rows); $i++){
    $row = $rows[$i];
    if(!empty($params)) foreach($params as $key => $value) $row[$key] = $value; //combinar datos a persisitir con los parametros


    //eliminar las pks persistidas del array de pks previamente consultado
    if(!empty($row["id"])) {
      $key = array_search($row["id"], $ids);
      if($key !== false) unset($ids[$key]);
    }

    $upload = $entityDao->uploadIndex_($transaction, $row, $i);
    $merge = array_merge($row, $upload["ids"]);
    $data = $entityDao->persist_($transaction, $merge);

    array_push($idsReturn, $data["id"]);
  }

  //si existen pks asociadas a los datos a persistir que quedaron sin procesar, eliminarlas
  foreach($ids as $id_) $entityDao->deleteById_($transaction, $id_);

  $transaction->commit();
  echo json_encode($idsReturn);

} catch (Exception $ex) {
  http_response_code(500);
  error_log($ex->getTraceAsString());
  echo ($ex->getMessage());
}