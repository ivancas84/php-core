<?php

require_once("class/model/Entity.php");

class _LogEntity extends Entity {
  public $name = "log";
  public $alias = "log";
  public $nf = ['id', 'type', 'description', 'user', 'created'];
  public $notNull = ['id'];
  public $unique = ['id'];


}
