<?php
    require_once("config/modelClasses.php");
    require_once("config/entityClasses.php");
    require_once("class/Transaction.php");
    require_once("class/Filter.php");
    try{
      //Inicializar
      $filter = new Filter();
      $name = $filter->requestRequired("entity");
      $text = $filter->requestRequired("text");


      $name_ = snake_case_to("XxYy", $name);
      $entityDaoName = $name_ . "Dao";
      $entityName = $name_ . "Entity";
      $entity = new $entityName;
      $entityDao = new $entityDaoName;
      
      $headers = array();
      foreach($entity->getFields() as $field) array_push($headers, $field->getName());
      
      try{

        //Recorrer archivo csv y definir transaccion
        $transaction = new Transaction();
        $transaction->begin();
        $errors = array();
        $rows = explode("\n", $text);

        for($i = 0; $i < count($rows); $i++) {
          $row_ = explode("\t", $rows[$i]);
          $row__ = array_map('trim', $row_);
          $row = array_combine($headers, $row__);
          try { $entityDao->persist_($transaction, $row); }
          catch (Exception $ex){ array_push($errors, $ex->getMessage() . " (fila " . $rowCount . ")"); } 
        }

        $transaction->commit();
      } finally { $entityDao->close(); }
    } catch (Exception $ex) {
      error_log($ex->getTraceAsString());
      http_response_code(500);
      echo $ex->getMessage();
    }
