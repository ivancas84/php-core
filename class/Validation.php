<?php
    require_once("class/Format.php");

    class Validation {

        public $patterns = array(
            'uri'           => '[A-Za-z0-9-\/_?&=]+',
            'url'           => '[A-Za-z0-9-:.\/_?&=#]+',
            'alpha'         => '[\p{L}]+',
            'words'         => '[\p{L}\s]+',
            'alphanum'      => '[\p{L}0-9]+',
            'int'           => '[0-9]+',
            'float'         => '[0-9\.,]+',
            'tel'           => '[0-9+\s()-]+',
            'text'          => '[\p{L}0-9\s-.,;:!"%&()?+\'°#\/@]+',
            'file'          => '[\p{L}\s0-9-_!%&()=\[\]#@,.;+]+\.[A-Za-z0-9]{2,4}',
            'folder'        => '[\p{L}\s0-9-_!%&()=\[\]#@,.;+]+',
            'address'       => '[\p{L}0-9\s.,()°-]+',
            'date_dmy'      => '[0-9]{1,2}\-[0-9]{1,2}\-[0-9]{4}',
            'date_ymd'      => '[0-9]{4}\-[0-9]{1,2}\-[0-9]{1,2}',
            'email'         => '[a-zA-Z0-9_.-]+@[a-zA-Z0-9-]+.[a-zA-Z0-9-.]+[.]+[a-z-A-Z]'
        );
     
        public $logs = array();

        public function logs(){ return $this->logs; }

        public function addLog($status, $data){
            if(!key_exists($this->key, $this->logs)) $this->logs[$this->key] = [];
            array_push($this->logs[$this->key], ["status" => $status, "data"=>$data]);
        }
         
        public function summary(){
            $status = "success";
        
            foreach($this->logs as $key => $value){
                foreach($value as $check){
                    switch ($check["status"]) {
                        case "error": return "error"; 
                        default: $status = $check["status"];        
                    }    
                }                      
            }
        
            return $status;
        }

        public function status(){
            if(!key_exists($this->key, $this->logs)) return "success";

            foreach($this->logs[$this->key] as $value){
                switch ($value["status"]) {
                    case "error": return "error"; 
                    default: $status = $value["status"];        
                }              
            }
          
            return $status;
        }
        
        public function key($name){
            //la asignacion de una nueva llave resetea los logs
            if(key_exists($this->key, $this->logs)) unset($this->logs[$this->key]);
            $this->key = $name;
            return $this;
        }
        
        public function value($value){
            $this->value = $value;
            return $this;
        }

        public function file($value){
            $this->file = $value;
            return $this;        
        }

        public function pattern($name){
            $regex = '/^('.$this->patterns[$name].')$/u';
            if($this->value != '' && !preg_match($regex, $this->value)) $this->addLog($this->key, "error", "Valor no válido");                                           
            return $this;
        }
        
        public function customPattern($pattern){
            $regex = '/^('.$pattern.')$/u';
            if($this->value != '' && !preg_match($regex, $this->value)) $this->addLog($this->key, "error", "Valor no válido");                                           
            return $this;
        }
        
        public function required(){
            if((isset($this->file) && $this->file['error'] == 4) || ($this->value == '' || $this->value == null))
                $this->addLog($this->key, "error", "Valor obligatorio sin definir");                           
            return $this;            
        }
        
        public function min($length){
            $data = "El valor es inferior al mínimo permitido";

            if(is_string($this->value)){                
                if(strlen($this->value) < $length) $this->addLog($this->key, "error", $data);           
            } else{
                if($this->value < $length) $this->addLog($this->key, "error", $data);           
            }
            return $this;
        }

        public function max($length){
            $data = "El valor supera al máximo permitido";
            if(is_string($this->value)){                
                if(strlen($this->value) > $length) $this->addLog($this->key, "error", $data);
            }else{                
                if($this->value > $length) $this->addLog($this->key, "error", $data);
            }
            return $this;            
        }
        
        public function equal($value){        
            if($this->value != $value)
                $this->addLog($this->key, "error", "El valor no coincide con {$value}");
            return $this;            
        }
        
        public function maxSize($size){        
            if($this->file['error'] != 4 && $this->file['size'] > $size)
                $this->addLog($this->key, "error", 'El archivo supera el tamaño máximo '.number_format($size / 1048576, 2).' MB.');            
            return $this;            
        }
        
        public function ext($extension){
            if($this->file['error'] != 4 && pathinfo($this->file['name'], PATHINFO_EXTENSION) != $extension && strtoupper(pathinfo($this->file['name'], PATHINFO_EXTENSION)) != $extension)              
              $this->addLog($this->key, "error", "El archivo no es no es {$extension} MB");              
            return $this;
            
        }
          
        public function string(){ if(!Format::isEmpty($this->value) && !is_string($this->value)) $this->addLog($key, "error", "El valor no es una cadena de caracteres"); }
        public function integer(){ if(!Format::isEmpty($this->value) && !is_integer($this->value)) $this->addLog($key, "error", "El valor no es un entero"); }
        public function float(){ if(!Format::isEmpty($this->value) && !is_float($this->value)) $this->addLog($key, "error", "El valor no es un flotante"); }
        public function boolean(){ if(!Format::isEmpty($this->value) && !is_boolean($this->value)) $this->addLog($key, "error", "El valor no es un booleano"); }
        public function date(){ if(!Format::isEmpty($this->value) && !is_a($value, 'DateTime')) $this->addLog($key, "error", "El valor no es una fecha"); }

    }