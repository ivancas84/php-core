# php-core

Version 0.8

* Eliminar atributos no utilizados de entity y field: subtype, select_values, table
* Incorporar atributos EntityQuery: fields_concat y group_concat. Se separa la logica de concatenacion de los atributos fields y group.
* Metodo EntityQuery.unique: El campo id debe definirse explicitamente como unico. Se redefinen las condiciones iniciales de concatenacion con la condicion unica
* Renombrar EntityQuery.fieldsQuery por EntityQuery.sql_fields

Version 0.7

* Cambio de camelCase a snake_case y traduccion de nombre para archivos de configuracion
* Nueva disposicion de elementos para ser invocados por el Container

Version 0.6

* Nueva asignación de nombres a los campos de respuesta: Los campos de respuesta coinciden con los de consulta

Version 0.5.2

* Incorporar constantes de configuracion general.
* Soporte para valor como field en la condicion.
* Definir nombre de la versión 1: SQLOrganize.
* Eliminar controladores SqlCondition y SqlOrder (serán incorporados en EntityQuery)


Version 0.5.1

* Definición de alias a traves de un identificador de field (fieldId). Se elimina el uso de la cadena de prefijos.
* Nueva clase EntityQuery que unifica las obsoletas EntityRender y EntitySqlo.
* Mapeo de la estructura de la base de datos con archivos json.
