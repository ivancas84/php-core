<?php

abstract class Import { //comportamiento general para importar datos
    public $file;
    public $pathSummary;
    
    public $ids = []; //array asociativo con identificadores
    public $dbs = []; //array asociativo con el resultado de las consultas a la base de datos
    public $elements = []; //array de elementos a importar

    
    public function execute(){
        echo date("Y-m-d H:i:s") . " BEGIN " . $this->file . "<br>";
        $this->define();
        $this->identify();
        $this->query();
        $this->process();
        $this->persist();
        $this->summary();
        echo date("Y-m-d H:i:s") . " END " . $this->file."<br>";
    }

    abstract public function element($i, $data);
        
    abstract public function identify();

    abstract public function query();

    abstract public function process();

    abstract public function summary();

    public function define(){
        if (($gestor = fopen("../../tmp/" . $this->file . ".csv", "r")) !== FALSE) {
            $encabezados = fgetcsv($gestor, 1000, ",");

            $i = 0;
            while (($datos = fgetcsv($gestor, 1000, ",")) !== FALSE) {
                $i++;
                if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') $datos = array_map("utf8_encode", $datos);
                $e = array_combine($encabezados, $datos);

                $this->element($i, $e);                  
                //if($i==100) break;           
            }
            fclose($gestor);
        }
    }

    public function persist(){
        //las etapas asociadas a una inscripcion, si existen, se eliminan y se vuelven a cargar
        $sql = "";
        foreach($this->elements as $element) {
            if(!$element->process) continue;
            
            $db = Dba::dbInstance();
            try {
                $sql .= $element->sql;
                $db->multiQueryTransaction($element->sql);
            } catch(Exception $exception){
                echo "<pre>";
                echo $exception->getMessage();
                echo "<br>";
                $element->process = false;
                $element->addError($exception->getMessage());
            } finally {
                Dba::dbClose();
            }
        }
        file_put_contents($this->pathSummary . ".sql", $sql);

    }



}