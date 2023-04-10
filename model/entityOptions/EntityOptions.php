<?php


class EntityOptions {

    /**
     * Todos los metodos en general se ejecutan comparando el valor UNDEFINED
     */
    public $prefix = "";
    
    public function _pf(){ return (empty($this->prefix)) ?  ''  : $this->prefix . '-'; } 
    /**
     * prefijo fields
     */
    
    public function _pt(){ return (empty($this->prefix)) ?  $this->container->entity($this->entity_name)->getAlias() : $this->prefix; }
    /**
     * prefijo tabla
     */

    function _callFields(array $field_names, $method = ""){
        /**
         * Ejecutar metodo en los fields indicados
         */
        foreach($field_names as $field_name) call_user_func_array([$this, "_".$method],[$field_name]);
        return $this;
    }

    function _call($method = ""){
        /**
         * Llamar a _callFields utilizando los field_names definidos en la entidad.
         */
        return $this->_callFields($this->container->entity($this->entity_name)->getFieldNames(), $method);
    }

    function _toArrayFields($field_names, $method = ""){
        /**
         * Ejecutar metodo y almacenar resultado en un array de fields
         * 
         * Por cuestiones operativas, no se utiliza el prefijo
         */
        $row = [];
        foreach($field_names as $field_name){
          $r = call_user_func_array([$this, "_".$method],[$field_name]);
          if($r !== UNDEFINED) $row[$field_name] = $r ;
        }

        return $row;
    }

    function _toArray($method = ""){
        /**
         * Ejecutar _toArrayFields para los campos definidos en la configuracion de la entidad principal
         */
        return $this->_toArrayFields($this->container->entity($this->entity_name)->getFieldNames(), $method);
    }

    function _fromArrayFields(array $row, $field_names, $method = ""){
        /**
         * Ejecutar metodo y almacenar resultado en atributos del objeto
         * 
         * Utiliza prefijo si esta definido
         */
        if(empty($row)) return $this;

        foreach($field_names as $field_name){
          if(array_key_exists($this->_pf().$field_name, $row)) call_user_func_array([$this, "_".$method],[$field_name, $row[$this->_pf().$field_name]]);
        }

        return $this;
    }

    function _fromArray(array $row, $method = ""){
        /**
         * Ejecutar _fromArrayFields para los atributos definidos en la configuracion de la entidad principal
         */
        return $this->_fromArrayFields($row, $this->container->entity($this->entity_name)->getFieldNames(), $method);
    }
    
}