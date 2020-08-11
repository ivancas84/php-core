<? require_once("../config/config.php"); ?>

<!DOCTYPE html>
<html>
<head>
<style>
textarea {
  width: 1000px;
  height: 150px;
}
</style>
</head>
<body>


<h2>Importar Persona</h2>

<form action="../script/1_persona.php" method="POST">
  <br>id:<br>
  <input type="text" name="id" value="persona"/>
  <br>Encabezados:<br>
  <textarea name="headers">numero_documento,apellidos,nombres,genero,telefono,email
  </textarea>
  <br>Source:<br>
  <textarea name="source"></textarea>
  <br>  
  <input type="submit" value="Submit">
</form> 

</body>
</html>
