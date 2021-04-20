<?php

/**
 * This class initiate new connection to database server
 */
class dbRules{
  private $CONN,
      $result,
      $fields_arr,
      $bind_result_param_arr,
      $prepare_result;
  public $num_rows = -1, 
      $affected_rows = -1,
      $error = 0,
      $stmt;

  public function __construct(){
    global $jarvis;

    try{
        $CONN = new mysqli(DBSERVER ,DBUSERNAME, DBPASSWORD, DBNAME);

        if(!$CONN->connect_errno)
            $this->CONN = $CONN;
        else
            throw new Exception($CONN->connect_error);

    }catch (Exception $e){
        //log error if DB connection failed
        $jarvis->e(-1,$e->getMessage()." \r\nOn Line: ".$e->getLine()." \r\nIn File: ".$e->getFile()."\r\n\r\n");
        die;
    }
    if($this->table_exist('TGUSER_TABLE_3J') !== 1 || $this->table_exist('TGDATA_TABLE_3J') !== 1){
      return $jarvis->e(-1, 'user or data table missing');
    }
  } 
  

    /**
     * Binds parameter to prepared statement
     * @param array $arr array containing values and their type corresponding to their query
     * [[$var,'s'],[$var2,'s']]
     * ### Must use
     * - s for string
     * - i for integer
     * - d for double
     * - b for blob
     * @return int|mixed
     * - -1 if error occured
     */
    private function bind_param($arr){
        $stmt = $this->stmt;
        $row=[];

        if(!$stmt){
            $this->parse_err('$dbclass::bind_param - $stmt is empty');
            return -1;
        }
        
        if(!$meta = $stmt->result_metadata()){
            
            //In some queries result_metadata return false which is not an error
            //For such queries, assign parameters to bind_param method only.
            $this->fields_arr = NULL;
            $this->bind_result_param_arr = NULL;
            $type = '';
            $fieldparam[0] = '';
            foreach($arr as $subarr){
                $fieldparam[] = &$subarr[0];
                $type .= $subarr[1];
            }
            $fieldparam[0] = $type;
            $temp = call_user_func_array(array($stmt, 'bind_param'), $fieldparam);
            if($temp === false){
                $this->parse_err('dbclass::bind_param error'.$stmt->error);
                return -1;
            }
            
            return;
        }
        
        while($field = $meta->fetch_field()){
            $name = $field->name;
            $fields[] = $name;
            $result_arr[$name] = &$row[$name];
        }
     
        $this->fields_arr = $fields;
        $this->bind_result_param_arr = $result_arr;

        $type = '';
        $fieldparam[0] = '';
        $i=0;
        foreach($arr as $subarr){
            $fieldparam[$fields[$i]] = &$subarr[0];
            $type .= $subarr[1];
        }
        $fieldparam[0] = $type;
        $temp = call_user_func_array(array($stmt, 'bind_param'), $fieldparam);
        if($temp === false){
            $this->parse_err('dbclass::bind_param error'.$stmt->error);
            return -1;
        }
        return $temp;
    }
    /**
     * function to check if a particular data exist in the database
     * @param string $table table name
     * @param string $field field name
     * @param int|bool|string|array|double|blob $data data to look for
     * @return int
     * - -1 error occured
     * - 0 $data not exist in the $field
     * - Value greater then 0 indicating how many times the data exist in the $field
     */
    public function check_data($table, $field, $data, $datatype = 's'){
        global $threej;
        $sql = "select $field from $table where $field = ?";
        $type = gettype($data);
        if($type === 'object' || $type === 'resource' || $type === NULL){
            return -1;
        }elseif($type === 'array'){
            $data = $threej->to_string($data);
            $arr[0] = [&$data,strval($datatype)];
        }else{
            switch($type){
                case 'double':
                    $arr[0] = [&$data,'d'];
                    break;
                case 'integer':
                    $arr[0] = [&$data,'i'];
                    break;
                default :
                    $arr[0] = [&$data,'s'];
            }
        }
        
        if($this->prepare($sql, $arr) === -1){return -1;}
        return $this->num_rows;

    }
    
    public function close(){
        if($this->CONN !== NULL)  $this->CONN->close();
        return 1;
    }
    public function create_table($table_const){
        $r = $this->CONN->query($table_const);
        if(!$r){
            $this->parse_err($this->CONN->error);
            return -1;
        }
        return $r;
    }
    /**
     * fetches result from database and must be called after prepare function
     * @return int|array
     * - -1 if error occured
     * - 0 if sql query results in 0 rows
     * - each rows data as array
     */
    public function execute(){
        $stmt =$this->stmt;
        $param = $this->bind_result_param_arr;

        if(!$stmt || $stmt === NULL || empty($stmt)){
            $this->parse_err('Empty $stmt object');
            return -1;
        }
        if(!$stmt->execute()){
            $this->num_rows = $stmt->num_rows;
            $this->affected_rows = $stmt->affected_rows;
            $this->parse_err($stmt->error);
            $stmt->close();
            return -1;
        }else{
            $this->num_rows = $stmt->num_rows;
            $this->affected_rows = $stmt->affected_rows;
        }

        if($param === NULL){
            $stmt->close();
            return;
        }
        if(!call_user_func_array(array($stmt,'bind_result'),$param)){
            $this->parse_err($stmt->error);
            $stmt->close();
            return -1;
        }
        
        if(!$stmt->store_result()){
            $this->parse_err($stmt->error);
            $stmt->close();
            return -1;
        };

        $this->num_rows = $stmt->num_rows;
        $this->affected_rows = $stmt->affected_rows;
        return $param;
    }
    /**
     * Fetch results from $stmt object
     * @return -1|0|array 
     * - -1 on error 
     * - 0 empty result
     * - array on success
     */
    public function fetch(){
        $stmt =$this->stmt;
        $param = $this->bind_result_param_arr;

        if($stmt !== NULL){
            if($stmt->fetch()){
                return $param;
            }           
        }
        return 0;
    }
    public function fetch_data(){
        return $this->result->fetch_row();
    }
    public function parse_err($msg)
    {
        $dt = array_reverse(debug_backtrace(0));
        $errloc = '';
        foreach($dt as $i){
            $errloc .= '['.basename($i['file']).':'.$i['line'].']';
        }
        $msg = strval($msg);
        $this->error = $msg;
        error_log("\r\n[".date("H:i:s d/m/Y",time())."]$errloc=>$ ".$msg,3,ROOT."db_error.log");
    }

    /**
     * @param string $sql sql statement
     * @param array $arr array containing values and their type corresponding to their query
     * ### Must use
     * - s for string
     * - i for integer
     * - d for double
     * - b for blob
     * @return int|object
     * - -1 if error occured
     * - stmt object
     */
    public function prepare($sql, $arr){
        $this->stmt = $this->CONN->prepare($sql);
        if($this->stmt === false){
            $this->stmt = NULL;
            $this->parse_err($this->CONN->error);
            return -1;
        }
        
        if($this->bind_param($arr) === -1){
            return -1;
        }

        if($this->execute() === -1){
            return -1;
        }
        
        return $this->stmt;
    }
    /**
     * Run query
     * @return -1|true|object
     * -1 on failure
     * true|query result on success
     */
    public function query($sql){
        $result = $this->CONN->query($sql);
        if(false === $result){
            $this->result = null;
            $this->parse_err($this->CONN->error);
            return -1;
        }

        $this->result = $result;
        return $result;
    }
    /**
     * @return -1|0|1 1 if table exist 0 if not -1 if error occured
     */
    public function table_exist($table_name){
        $table_name = $this->CONN->escape_string($table_name);
        $result = $this->CONN->query("show tables like '$table_name'");
        if(!$result){
            $this->parse_err('db query failed for table: '.$table_name);
            return -1;
        }
        if(!$result->num_rows){
            return 0;
        }else{
            return 1;
        }
    }
}
?>