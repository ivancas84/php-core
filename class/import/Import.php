<?php

abstract class Import { //comportamiento general para importar datos
    public static $archivo;
    public static $elements = []; //array de ImportElement

    public static function execute(){
        echo date("Y-m-d H:i:s") . " BEGIN " . self::$archivo . "<br>";
        self::define();
        self::identify();
        self::query();
        self::process();
        self::persist();
        self::summary();
        echo date("Y-m-d H:i:s") . " END " . self::$archivo."<br>";
    }

    abstract public static function element($i, $data);
        
    abstract public static function identify();

    abstract public static function query();

    abstract public static function process();

    abstract public static function summary();

    public static function define(){
        if (($gestor = fopen("../../tmp/" . CsvImport::$archivo . ".csv", "r")) !== FALSE) {
            $encabezados = fgetcsv($gestor, 1000, ",");

            $i = 0;
            while (($datos = fgetcsv($gestor, 1000, ",")) !== FALSE) {
                $i++;
                if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') $datos = array_map("utf8_encode", $datos);
                $e = array_combine($encabezados, $datos);

                self.element($i, $e);                  
                //if($i==1000) break;           
            }
            fclose($gestor);
        }
    }

    public static function persist(){
        //las etapas asociadas a una inscripcion, si existen, se eliminan y se vuelven a cargar
        foreach(self::$elements as $element) {
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