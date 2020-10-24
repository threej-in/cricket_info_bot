<?php

define('DBSERVER', ''); //enter server
define('DBUSERNAME', ''); //enter DB username
define('DBPASSWORD', ''); //enter DB password
define('DBNAME', ''); //enter DB name

//$GLOBALS[$CONN];
$CONN = mysqli_connect(DBSERVER, DBUSERNAME, DBPASSWORD);

if(!$CONN){
    send_log("Database connection error :( ");
}else{
    createdb($CONN);
}
/*Check if database exist or not
    SELECT SCHEMA_NAME
    FROM INFORMATION_SCHEMA.SCHEMATA
    WHERE SCHEMA_NAME = 'DBName'*/
function createdb($CONN){
    $sql = "CREATE DATABASE IF NOT EXISTS ".DBNAME." ;";
    if(!mysqli_query($CONN, $sql)){
        send_log("Database creation error :( ");
        return false;
    }
}
$CONN = mysqli_connect(DBSERVER, DBUSERNAME, DBPASSWORD, DBNAME);
if(!$CONN){
    send_log("Database connection error :( ");
    die();
}