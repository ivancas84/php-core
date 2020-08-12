<?php
     
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
            'email'         => '[a-zA-Z0-9_.-]+@[a-zA-Z0-9-]+.[a-zA-Z0-9-.]+[.]+[a-z-A-Z]',
            'name'          => '[^a-zA-ZáéíóúñÁÉÍÓÚÑçÇüÜ\s\']'
        );

        public $errors = array();
        public $value;
        public $file;
        
        public static function getInstanceValue($value) {
            $v = new Validation();
            $v->value = $value;
            return $v;
        }

        public static function getInstanceFile($value) {
            $v = new Validation();
            $v->file = $value;
            return $v;
        }

        public function value($value) {
            $this->value = $value;
            return $this;
        }
        
        public function file($value) {
            $this->file = $value;
            return $this;
        }
        
        public function pattern($name) {
            if($name == 'array'){
                if(!is_array($this->value)) {
                    $this->errors[] = 'Formato no válido.';
                }
            } else {
                $regex = '/^('.$this->patterns[$name].')$/u';
                if($this->value != '' && !preg_match($regex, $this->value)) {
                    $this->errors[] = 'Formato no válido.';
                }
            }
            return $this;
        }

        public function name() {
          if($this->value != '' && preg_match('/[^a-zA-ZáéíóúñÁÉÍÓÚÑçÇüÜ\s\']/', $this->value))
            $this->errors[] =  'Formato no válido.';
          return $this;
        }

        public function customPattern($pattern) {
            $regex = '/^('.$pattern.')$/u';
            if ($this->value != '' && !preg_match($regex, $this->value)) {
                $this->errors[] = 'Formato no válido.';
            }
            return $this;
        }

        public function required() {
            if ((isset($this->file) && $this->file['error'] == 4) 
            || ($this->value === '' || $this->value === null)) {
                $this->errors[] = 'Campo obligatorio.';
            }            
            return $this;
        }
        
        public function min($length) {
            if (is_string($this->value)) {
                if (strlen($this->value) < $length) {
                    $this->errors[] = 'Valor inferior al mínimo';
                }
            } else {
                if ($this->value < $length) {
                    $this->errors[] = 'Valor inferior al mínimo';
                }
            }
            return $this;
        }

        public function max($length) {
            if (is_string($this->value)){                
                if(strlen($this->value) > $length){
                    $this->errors[] = 'Valor superior al máximo';
                }
            } else {
                if($this->value > $length){
                    $this->errors[] = 'Valor superior al máximo';
                }
            }
            return $this;
        }

        public function equal($value) {
            if($this->value != $value){
                $this->errors[] = 'Valor no correspondiente.';
            }
            return $this;
        }

        public function differentWords($value){
          $val1 = explode(" ", strtolower(trim(str_replace("  ", $this->value))));
          $val2 = explode(" ", strtolower(trim(str_replace("  ", $value))));
          foreach($val1 as $v1) {
            foreach($val2 as $v2){
              if(strtolower($v1) == strtolower($v2)) {
                $this->errors[] = 'Palabras iguales.';
                break;
              }
            }
          }
      
          return $this;
        }


        public function maxSize($size) {
            if($this->file['error'] != 4 && $this->file['size'] > $size){
                $this->errors[] = 'El archivo supera el tamaño máximo de '.number_format($size / 1048576, 2).' MB.';
            }
            return $this;
        }

        public function ext($extension) {
            if($this->file['error'] != 4 && pathinfo($this->file['name'], PATHINFO_EXTENSION) != $extension && strtoupper(pathinfo($this->file['name'], PATHINFO_EXTENSION)) != $extension){
                $this->errors[] = 'El archivo no es '.$extension.'.';
            }
            return $this;
        }


         
        public function isSuccess() {
          return (empty($this->errors)) ? true : false;
        }
        
        public function getErrors() {
            return $this->errors;
        }
        
        public function displayErrors() {
            $html = '<ul>';
            foreach ($this->getErrors() as $error) {
                $html .= '<li>'.$error.'</li>';
            }
            $html .= '</ul>';
            
            return $html;        
        }

        public function email(){
          if(!self::is_empty($this->value) && !self::is_email($this->value)) $this->errors[] = "El valor no es un email";
            return $this;
        }
        
        public function string() { 
            if(!self::is_empty($this->value) && !is_string($this->value)) $this->errors[] = "El valor no es una cadena de caracteres";
            return $this;
        }
        
        public function integer() { 
            if(!self::is_empty($this->value) && !is_integer($this->value)) $this->errors[] =  "El valor no es un entero";
            return $this;
        }
        
        public function float() { 
            if(!self::is_empty($this->value) && !is_float($this->value)) $this->errors[] =  "El valor no es un flotante";
            return $this;
        }

        public function boolean() { 
            if(!self::is_empty($this->value) && !is_bool($this->value)) $this->errors[] =  "El valor no es un booleano";
            return $this;
        }

        public function date() {
            if(!self::is_undefined($this->value) 
            && !is_null($this->value) 
            && !is_a($this->value, 'DateTime')) 
                $this->errors[] =  "El valor no es una fecha/hora";
            return $this;
        }

        public function empty() {
          if(self::is_empty($this->value)) $this->errors[] =  "El valor esta vacio";
            return $this;
        }

        public function abbreviation() {
          $vals = explode(" ", trim(str_replace("  "," ", $this->value)));
          
          foreach($vals as $val) {
            if(strlen($val) < 2) {
              $this->errors[] =  "Posible abreviatura";
              break;
            }
          }

          return $this;
        }

        public static function purify($string) {
            return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
        }

        public static function is_int($value) {
            if(filter_var($value, FILTER_VALIDATE_INT)) return true;
        }
     
        public static function is_float($value) {
            if(filter_var($value, FILTER_VALIDATE_FLOAT)) return true;
        }
        
        public static function is_alpha($value) {
            if(filter_var($value, FILTER_VALIDATE_REGEXP, array('options' => array('regexp' => "/^[a-zA-Z]+$/")))) return true;
        }
      
        public static function is_alphanum($value) {
            if(filter_var($value, FILTER_VALIDATE_REGEXP, array('options' => array('regexp' => "/^[a-zA-Z0-9]+$/")))) return true;
        }
        
        public static function is_url($value) {
            if(filter_var($value, FILTER_VALIDATE_URL)) return true;
        }
       
        public static function is_uri($value) {
            if(filter_var($value, FILTER_VALIDATE_REGEXP, array('options' => array('regexp' => "/^[A-Za-z0-9-\/_]+$/")))) return true;
        }
      
        public static function is_bool($value) {
            if(is_bool(filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE))) return true;
        }
    
        public static function is_email($value) {
            if(filter_var($value, FILTER_VALIDATE_EMAIL)) return true;
        }

        public static function is_empty($value) {
            return ($value === UNDEFINED || empty($value)) ? true : false;
        }

        public static function is_undefined($value) {
            return ($value === UNDEFINED) ? true : false;
        }
    }