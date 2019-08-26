<?php

abstract class Import { //comportamiento general para importar datos
    public $archivo;

    public $ids = []; //array asociativo con identificadores
    public $dbs = []; //array asociativo con el resultado de las consultas a la base de datos
    public $elements = []; //array de elementos a importar

    public function execute(){
        echo date("Y-m-d H:i:s") . " BEGIN " . $this->$archivo . "<br>";
        $this->define();
        $this->identify();
        $this->query();
        $this->process();
        $this->persist();
        $this->summary();
        echo date("Y-m-d H:i:s") . " END " . $this->$archivo."<br>";
    }

    abstract public function element($i, $data);
        
    abstract public function identify();

    abstract public function query();

    abstract public function process();

    abstract public function summary();

    public function define(){
        if (($gestor = fopen("../../tmp/" . $this->archivo . ".csv", "r")) !== FALSE) {
            $encabezados = fgetcsv($gestor, 1000, ",");

            $i = 0;
            while (($datos = fgetcsv($gestor, 1000, ",")) !== FALSE) {
                $i++;
                if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') $datos = array_map("utf8_encode", $datos);
                $e = array_combine($encabezados, $datos);

                $this->element($i, $e);                  
                //if($i==1000) break;           
            }
            fclose($gestor);
        }
    }

    public function persist(){
        //las etapas asociadas a una inscripcion, si existen, se eliminan y se vuelven a cargar
        foreach($this->$elements as $element) {
            if(!$element->process) continue;
            
            $db = Dba::dbInstance();
            try {
                $db->multiQueryTransaction($element->sql);
            } catch(Exception $exception){
                //echo "<pre>";
                //echo $element->sql;
                $element->process = false;
                $element->addError($exception->getMessage());
            } finally {
                Dba::dbClose();
            }
        }
    }



}