<?php

require_once("class/model/Render.php");

//Presentacion de datos
class RenderAux extends Render {
  /**
   * Implementa metodos auxiliares a los que utiliza Render
   * Habitualmente son utilizados para consultas avanzadas que utilicen group by y funciones de agregacion
   */

  protected $aggregate = array(); //campos a los que se aplicara funciones de agregacion
  /**
   * Deben estar definidos en el método mapping field, se realizará la traducción correspondiente
   * Ej ["sum_horas_catedra", "avg_edad"]
   */

  protected $group = array(); //campos de agrupacion
  /**
   * Deben ser campos de consulta
   * Ej ["profesor", "cur_horario"]
   */

  protected $having = array(); //condicion avanzada de agrupamiento, similiar a condicion avanzadas
  /**
   * array multiple cuya raiz es [field,option,value], ejemplo: [["nombre","=","unNombre"],[["apellido","=","unApellido"],["apellido","=","otroApellido","OR"]]]
   */

  public function setAggregate (array $aggregate = null) { $this->aggregate = $aggregate; }
  public function getAggregate () { return $this->aggregate; }

  public function setGroup (array $group = null) { $this->group = $group; }
  public function getGroup () { return $this->group; }

  public function setHaving (array $having = null) { $this->having = $having; }
  public function getHaving () { return $this->having; }
}
