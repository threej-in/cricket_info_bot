<?php
define('USER_TABLE','CREATE TABLE USER_TABLE(
    id double AUTO_INCREMENT PRIMARY KEY,
    chat_id int(10) NOT NULL,
    username varchar(128),
    first_name varchar(128),
    message_id int,
    j_date datetime,
    lu_date datetime
    );'
  );
define('DATA_TABLE','CREATE TABLE DATA_TABLE(
id INT AUTO_INCREMENT PRIMARY KEY,
identifier text not null,
fdata text not null,
fdate datetime not null
);'
);
/**
 * This class initiate new connection to database server
 */
class db_conn{
    public $CONN = NULL;
    /**
     * Function to create a database with the name defined in DBNAME constant.
     */
    private function createdb($DBSCONN){
        global $COM;

        $sql = "CREATE DATABASE IF NOT EXISTS ".DBNAME.";";

        if(!mysqli_query($DBSCONN, $sql)){
            $COM->send_log("Database creation error :( ".mysqli_error($DBSCONN));
            die;
        }
    }

    function __construct(){
        global $COM;
        $DBSCONN = mysqli_connect(DBSERVER, DBUSERNAME, DBPASSWORD);

        if(!$DBSCONN){
            $COM->send_log("Server connection error :( ");
            die;
            
        }else{
            $sql = "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '".DBNAME."';";
            $is_DB_found = mysqli_query($DBSCONN, $sql);

            if(!$is_DB_found){
                $this->createdb($DBSCONN);
                $CONNECTION = mysqli_connect(DBSERVER, DBUSERNAME, DBPASSWORD, DBNAME);
                
            }else{
                $CONNECTION = mysqli_connect(DBSERVER, DBUSERNAME, DBPASSWORD, DBNAME);
            }
            if(!$CONNECTION){
                $COM->send_log("Database connection error:( ");
                die;
            }else{
                $this->CONN = $CONNECTION;
            }
            
        }
    }
    
}

//function for database manipulations
function dbman($table, $mysql){
    global $CONN, $COM;
    $sql = "SELECT * FROM $table;";

    $result = mysqli_query($CONN, $sql);
    if(!$result){
      if(strcmp($table, 'USER_TABLE') === 0)
        {$result = mysqli_query($CONN, USER_TABLE);}
      else
        {$result = mysqli_query($CONN, DATA_TABLE);}
      if(!$result){
        $COM->send_log("$table creation error:".mysqli_error($CONN));
        return false;
      }
    }
    $result = mysqli_query($CONN, $mysql);
    if(!$result){
      $COM->send_log("User finding error: ".mysqli_error($CONN));
      return false;
    }else{
      return $result;
    }
  }
  //End of dbman
  
  function db_user($user){
    global $CONN, $COM;
    $mysql = "SELECT first_name FROM USER_TABLE WHERE chat_id = {$user['id']};";
    $result = dbman('USER_TABLE', $mysql);
    if($result === false) { return false;}
    elseif($result->num_rows === 0){
      $sqli = "SELECT * FROM USER_TABLE;";
      $resulti = mysqli_query($CONN, $sqli);
      $COM->send_log("<b>New User</b>\nTotal users: {$resulti->num_rows} \nName: <a href=\"tg://user?id={$user['id']}\">{$user['fname']}</a>\nUsername: @{$user['usrname']}");
      $sql = "INSERT INTO USER_TABLE (chat_id, username, first_name, message_id, j_date, lu_date) VALUES ({$user['id']},'{$user['usrname']}','{$user['fname']}','{$user['msgid']}',{$user['jdate']},{$user['lu_date']});";
      $result = mysqli_query($CONN, $sql);
      if(!$result){
        $COM->send_log("New user data insertion error: ".mysqli_error($CONN));
        return false;
      }
    }//if user already exists then only update the message_id column
    elseif($result->num_rows === 1){
      $sql =" UPDATE USER_TABLE SET message_id = {$user['msgid']},lu_date = {$user['lu_date']} WHERE chat_id = {$user['id']};";
      
      $result = mysqli_query($CONN, $sql);
      if(!$result){
        $COM->send_log("User message id updation error: ".mysqli_error($CONN));
        return false;
      }
    }
    return true;
  }
  
  function db_data($identifier){
    global $CONN, $COM;

    $mysql = "SELECT * FROM DATA_TABLE WHERE identifier LIKE \"$identifier\";";
    $result = dbman('DATA_TABLE', $mysql);
    
    if(!$result){
      $COM->send_log("Data fetching error: ".mysqli_error($CONN));
      return false;
    }

    if($result->num_rows === 0){
      return 0;
    }
    $result = mysqli_fetch_assoc($result);
    
    $diff = abs((strtotime($result['fdate']) - time()))/60;
    if($diff > 5){
      return 4;
    }

    $fdata = $result['fdata'];
    return $fdata;
  }
