<?php

require_once "config_3j.php";

empty(BOT_TOKEN) && printf("Bot_token must not be empty.<br>You can get one from http://t.me/botfather") && exit;
define('API_URL', "https://api.telegram.org/bot".BOT_TOKEN."/");

/**
 * Class for handling communications with telegram api
 * @since 0.1.0 
 */
class communication{

  /**
   * curl_handler function handles request and response from and to the telegram api
   * @return mixed TRUE on success and response array if needed and string on failure
   * @param array $parameter parameters array as specified in telegram bots api documents
   * @param bool $response_needed If you need the response from curl then send 1
   */
  public function curl_handler($parameter, $response_needed = 0){

    (empty($parameter)) && $parameter = array(); //declare an empty array if $parameter is empty
    
    if(!is_array($parameter)){
      return "Parameters must be an array\n";
    }
    if(!isset($parameter['method'])){
      return "Method required to send the data\n";
    }

    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL=>API_URL,
        CURLOPT_RETURNTRANSFER=>true,
        CURLOPT_POST=>TRUE,
        CURLOPT_POSTFIELDS=>json_encode($parameter),
        CURLOPT_HTTPHEADER=>array('content-Type: application/json'),
        CURLOPT_CONNECTTIMEOUT=>4,
        CURLOPT_TIMEOUT=>5
      ]
    );

    $response = curl_exec($ch);
  
    $server_r_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    //uncomment next line for debugging
    echo "<br>Server response code: $server_r_code <br>";
    
    $err_no = intval(curl_errno($ch));
    //uncomment next line for debugging
    echo "<br>Curl error:".curl_error($ch)."<br>";
    
    if($err_no !== 0 || $server_r_code !== 200) {

      error_log(curl_error($ch)."\n\n".$response);
      return false;

    }

    curl_close($ch);
    return ($response_needed === 1) ? $response : true ;

  }
  //End of curl_handler method

  /**
   * function to report errors to admin directly by sending a private chat on telegram
   * @param mixed $msg log message which is sent to admin
   * @return NULL no return value!
   */
  public function send_log($msg){
    
    if(empty(ADMINID)){
      error_log($msg);
      
    }else{
      
      if(is_string($msg)){

        $decoded = json_decode($msg, true);
        $err = json_last_error();
        //if json_decode function decodes the string successfully then build the http query.
        ($err > 0 && $err < 5) || $msg = http_build_query($decoded, ' ', ' ');
        
      }elseif(is_array($msg)){
        $msg = http_build_query($msg, '', ' ');
      }else{
        $msg = strval($msg);
      }

      //string cleaning for more readability
      $find = ['/%2f/i','/%5D/i', '/%5B/i', '/\s+/'];
      $replace = ['/', ']', '[', ' '];
      $msg = preg_replace($find,$replace, $msg);

      $parameter = [
        'method'=>'sendmessage',
        'chat_id' => ADMINID,
        'text' => $msg,
        'parse_mode' => 'HTML'
      ];
      $this->curl_handler($parameter);
    }
  }
  //end of send log method

  /**
   * Actions notify user about bot's current status
   * @param int $chat_id
   * @param string $action action type default is typing
   * @return NULL no return value
   */
  public function send_action($chat_id, $action = "typing"){

    is_string($action) || $action = strval($action);
    $parameter = [
      'method'=>'sendchataction',
      'chat_id'=>$chat_id,
      'action'=> $action
    ];
    $this->curl_handler($parameter);

  }
  public function report_error($msg_for_admin, $chat_id = ADMINID, $msg_for_user = "Unknown error occured"){
      $parameter = [
        'method' => "sendmessage",
        'text' => $msg_for_user,
        'chat_id' => $chat_id
      ];
      $this->curl_handler($parameter);

      $this->send_log($msg_for_admin);
  }

}