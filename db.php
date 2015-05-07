<?php 
//Basic mysql database adapter

class db {
  private $mysqli = false; 
  private $id_var = false;
  private $default_function = false;

  public function __construct($db_name = DB_NAME, $id_var = 'id') {
    $this->setDB($db_name);
    $this->setIDVar($id_var);
    $this->setDefaultFunction($function = function($row) {return $row;});
  }

  public function __destruct() {
    $this->closeDB();
  }

  private function closeDB() {
    return $this->mysqli->close();
  }

  public function getDB() {return $this->mysqli;}

  public function getIDVar() {return $this->id_var;}

  public function getDefaultFunction() {return $this->default_function;}

  public function insertQuery($array, $table_name) {
    if(!is_array($array)) die(print_r($array, true));
    return 'INSERT INTO `' . $table_name . '` (`' . implode('`, `', array_keys($array)) . '`) VALUES ("' . implode('", "', $array) . '");';
  }

  public function query($query, $function = false) {
    if(!$function) $function = $this->getDefaultFunction();
    $db = $this->getDB();
    $id_var = $this->getIDVar();

    $results = array();
    if($result = $db->query($query)) {
      if(($result === false) || (@$result->num_rows == 0)) {
        if(strpos(strtolower($query), 'select') === false) return false;
        return true;
      }

      while ($row = $result->fetch_assoc()) {
        $entry = $function($row);
        if($entry !== false) $results[$row[$id_var]] = $this->sanitizeText($entry);
      }
      $result->free();
    }

    return $results;
  }

  //Only comments out quotation marks
  public function sanitizeText($text) {
    if(is_array($text)) {
      foreach($text as $id => $item) $text[$id] = $this->sanitizeText($item);  
      return $text;
    }
    $text = str_replace("\n", "<br>", $text);
    return utf8_encode(str_replace(array('&quot;', '&lt;', '&gt;', '&nbsp;', '&amp;', '&rsquo;', '&lsquo;'), array('\"', '<', '>', '', '&', '\"', '\"'), htmlentities($text)));
  } 

  private function setDB($name, $user = DB_USER, $pass = DB_PASS, $host = DB_HOST) {
    $this->mysqli = new mysqli($host, $user, $pass, $name);
    if($this->mysqli->connect_errno) {
      printf("Connect failed: %s\n", $this->mysqli->connect_error);
      exit();
    }
  }

  private function setDefaultFunction($function) {
    $this->default_function = $function;
  }

  public function setIDVar($var) {
    $this->id_var = $var;
  }


}

