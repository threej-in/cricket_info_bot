<?php

/**
 * Telegram bot script for fetching cricket data from cricbuzz website.
 * 
 * @link http://threej.in
 * @author threej [ Jitendra Pal ]
 * 
 * licence under GNU General Public License v3.0
 * licence @link https://github.com/threej-in/cricket_info_bot/blob/main/LICENSE
 * @version 0.1.0
 */

require_once 'includes/msg_processor.php';

//set webhook using query parameter
if(isset($_GET)){

  if(isset($_GET['AAE8QCd9apUDBu5QO_q3FPEmBxBEhlfPv-o_swbhkurl'])){

    $parameter = ['method'=>'setwebhook','url' => $_GET['AAE8QCd9apUDBu5QO_q3FPEmBxBEhlfPv-o_swbhkurl']];
    $result = $COM->curl_handler($parameter, 1);
    echo $result;

  }else{
    echo "<h2>404 World is missing</h3><br>"; //show error to unknown visitors 
  }
}else{
  echo "<h2>404 World is missing</h3><br>"; //show error to unknown visitors 
}

//get update and send for processing
$message = file_get_contents("php://input");
new message($message);

mysqli_close($CONN);
