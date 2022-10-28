<?php


class ExportData {
  
  protected $data;
  protected $fileName;
  
  public function __construct(array $data, $fileName = null) {
    $this->data = $data;
    $this->fileName = (!empty($fileName)) ? $fileName : "export_" . date('Ymd');
  }
  
  //***** limpiar texto xls ****
  protected function cleanDataXls(&$str){
    // escape tab characters
    $str = preg_replace("/\t/", "\\t", $str);
    
    // escape new lines
    $str = preg_replace("/\r?\n/", "\\n", $str);
    
    // convert 't' and 'f' to boolean values (postgres)
    if($str == 't') $str = 'SI';
    if($str == 'f') $str = 'NO';
    
    // force certain number/date formats to be imported as strings
    //The types of values being escape this way are: values starting with a zero; values starting with an optional + and at least 8 consecutive digits (phone numbers); and values starting with numbers in YYYY-MM-DD format (timestamps). 
    //The section that prevents values being scrambled does so by inserting an apostrophe at the start of the cell. When you open the resuling file in Excel you may see the apostrophe, but editing the field will make it disappear while retaining the string format. Excel is strange that way.
    if(preg_match("/^0/", $str) || preg_match("/^\+?\d{8,}$/", $str) || preg_match("/^\d{4}.\d{1,2}.\d{1,2}/", $str)) $str = "'$str";
    
    // escape fields that include double quotes
    //Without this an uneven number of quotes in a string can confuse Excel.
    if(strstr($str, '"')) $str = '"' . str_replace('"', '""', $str) . '"'; 
    
    $str = mb_convert_encoding($str, 'UTF-16LE', 'UTF-8'); //If like us your data contains UTF-8 characters you will notice that Excel doesn't handle them very well. Other applications can open UTF-8 content without problems, but Microsoft still occupies the dark ages. Convert everything from UTF-8 to UTF-16 Lower Endian (UTF-16LE) format which Excel, at least on Windows, will recognise.
  }
  
  //***** limpiar texto csv ****
  protected function cleanDataCsv(&$str){    
    // convert 't' and 'f' to boolean values (postgres)
    if($str == 't') $str = 'SI';
    if($str == 'f') $str = 'NO';
    
    // force certain number/date formats to be imported as strings
    //The types of values being escape this way are: values starting with a zero; values starting with an optional + and at least 8 consecutive digits (phone numbers); and values starting with numbers in YYYY-MM-DD format (timestamps). 
    //The section that prevents values being scrambled does so by inserting an apostrophe at the start of the cell. When you open the resuling file in Excel you may see the apostrophe, but editing the field will make it disappear while retaining the string format. Excel is strange that way.
    if(preg_match("/^0/", $str) || preg_match("/^\+?\d{8,}$/", $str) || preg_match("/^\d{4}.\d{1,2}.\d{1,2}/", $str)) $str = "'$str";
    
    // escape fields that include double quotes
    //Without this an uneven number of quotes in a string can confuse Excel.
    if(strstr($str, '"')) $str = '"' . str_replace('"', '""', $str) . '"'; 
  }
  
  public function exportXls(){
    header("Content-Disposition: attachment; filename=\"" . $this->fileName . ".xls\"");
    header("Content-Type: text/csv; charset=UTF-16LE");

    $flag = false;
    foreach($this->data as $row) {
      if(!$flag) {
        // display field/column names as first row
        echo implode("\t", array_keys($row)) . "\r\n";
        $flag = true;
      }
      array_walk($row, array($this, 'cleanDataXls'));
      echo implode("\t", array_values($row)) . "\r\n";
    }
    exit;
  }
  
  
  //Newer versions of Excel are becoming fussy about opening files with a .xls extension that are not Excel binary files, making CSV format with a .csv extension a better option.
  public function exportCsv(){
    header("Content-Disposition: attachment; filename=\"" . $this->fileName . ".csv\"");
    header('Content-Type: text/csv; charset=utf-8');

    $out = fopen("php://output", 'w'); //tricking it into writing directly to the page by telling it to write to  php://output instead of a regular file. A nice trick.

    $flag = false;
    foreach($this->data as $row) {
      if(!$flag) {
        // display field/column names as first row
        fputcsv($out, array_keys($row), ',', '"');
        $flag = true;
      }
      array_walk($row, array($this, 'cleanDataCsv'));
      fputcsv($out, array_values($row), ',', '"');

    }
    
    fclose($out);

    exit;
  }
  
  
  public function exportHtml(){
    
    if(!count($this->data)) {
      echo "no hay datos para mostrar";
      return;
    }
    
    $encabezados = $this->data[0];
    echo "<head>
<meta http-equiv=\"content-type\" content=\"text/html; charset=utf-8\" />
<style>
table { border-collapse: collapse; }
table, th, td { border: 1px solid black; }
</style>
</head>
<body>";
  
    echo "<table>";
    echo "<tr>";
    foreach($encabezados as $key => $row){
      echo "<th>" . $key . "</th>";
    }
    echo "</tr>";
    
    foreach($this->data as $row) {
      array_walk($row, array($this, 'cleanDataCsv'));

      echo "<tr>";
      foreach($row as $key => $field) echo "<td>" . $field . "</td>";
      echo "</tr>";
    }
    echo "</table>
</body>";
  }
}
