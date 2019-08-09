<?php
    require_once("config/modelClasses.php");
    require_once("config/entityClasses.php");
    require_once("class/Transaction.php");
    require_once("class/Filter.php");
    try{
      //Inicializar
      $filter = new Filter();
      $entity = $filter->requestRequired("entity");
      $csv = $filter->fileRequired("csv");
      if(($csv["type"] != "text/csv") && ($csv["type"] != "application/vnd.ms-excel")) throw new Exception("Tipo de archivo CSV incorrecto");

      //***** procesar archivo *****
      $handle = fopen($csv['tmp_name'], 'r') ; 
      if(!$handle) throw new Exception("Error al abrir el archivo");
      
      try {

        $entityDaoName = snake_case_to("XxYy", $entity) . "Dao";
        $entityDao = new $entityDaoName;
        try{
          $headersAux = fgetcsv($handle, 0, ';'); if(is_null($headersAux)) throw new Exception("Archivo CSV no válido");
          $headers = array_map('trim', $headersAux);

          //Recorrer archivo csv y definir transaccion
          $transaction = new Transaction();
          $transaction->begin();
          $rowCount = 0; //se utiliza un contador para indicar el numero de fila correspondiente al error, si es que existe alguno
          $errors = array();
          while (($dataCsv = fgetcsv($handle, 0, ';')) !== FALSE) {
            $rowCount++;

            $dataAux = array_map('trim', $dataCsv);
            $row = array_combine($headers, $dataAux);
            
            try { 
              $entityDao->persist_($transaction, $row);              
            }
            catch (Exception $ex){ array_push($errors, $ex->getMessage() . " (fila " . $rowCount . ")"); } 
          }
          
          $transaction->commit();
        } finally { $entityDao->close(); }
      } finally { fclose($handle); }
    } catch (Exception $ex) {
      http_response_code(500);
      error_log($ex->getTraceAsString());
      echo $ex->getMessage();
    }
