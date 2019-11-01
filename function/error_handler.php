<?php
//set_error_handler("error_handler", E_WARNING);
//set_error_handler("error_handler", E_NOTICE);
//restore_error_handler();

function error_handler($errno, $errstr, $errfile, $errline)
{
    switch ($errno) {
       case E_WARNING:
                echo "WARNING ".$errstr;                
                /* No ejecutar el gestor de errores interno de PHP, hacemos que lo pueda procesar un try catch */
                return true;
                break;
            
            case E_NOTICE:
                echo "NOTICE ".$errstr;                
                /* No ejecutar el gestor de errores interno de PHP, hacemos que lo pueda procesar un try catch */
                return true;
                break;
            
            default:
                /* Ejecuta el gestor de errores interno de PHP */
                return false;
                break;
            }
}

