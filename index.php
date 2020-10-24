<?php
define('BOT_TOKEN', ''); //enter bots unique token
define('API_URL', 'https://api.telegram.org/bot'.BOT_TOKEN.'/');
define('ADMINID',''); //enter admin id to receive the logs
define('USER_TABLE',"CREATE TABLE USER_TABLE(
  id double AUTO_INCREMENT PRIMARY KEY,
  chat_id int(10) NOT NULL,
  username varchar(128),
  first_name varchar(128),
  message_id int,
  j_date datetime
  );");
define('DATA_TABLE',"CREATE TABLE DATA_TABLE(
  id INT AUTO_INCREMENT PRIMARY KEY,
  identifier text not null,
  fdata text not null,
  fdate datetime not null
  );");

//include the required files
require 'dbconfig.php';
require 'simple_html_dom.php';
require 'scrapper.php';

//function to handle curl requests
function curl_handler($parameter, $response_needed){
  if(!$parameter){
   $parameter = array();
  }elseif(!is_array($parameter)){
    return "Parameters must be an array\n";
  }
  $e = curl_init();
  curl_setopt_array($e, [
      CURLOPT_URL=>API_URL,
      CURLOPT_RETURNTRANSFER=>true,
      CURLOPT_POST=>TRUE,
      CURLOPT_POSTFIELDS=>json_encode($parameter),
      CURLOPT_HTTPHEADER=>array("content-Type: application/json"),
      CURLOPT_CONNECTTIMEOUT=>4,
      CURLOPT_TIMEOUT=>5
    ]
  );
  $response = curl_exec($e);

  $server_r_code = curl_getinfo($e, CURLINFO_HTTP_CODE);
  echo "<br>Server response code:".$server_r_code;
  $err_no = intval(curl_errno($e));
  echo "<br>Curl error:".curl_error($e);
  
  if($err_no !== 0 || $server_r_code !== 200) {
    send_log(curl_error($e)."\n\n".$response);
    return false;
  }
  curl_close($e);

  return (($response_needed === 1) ? ((!$response) ? false : $response) : true );
}
//END of curl handler function

//send log to admin chat privately
function send_log($log_msg){
  $decoded = json_decode($log_msg, true);
  $e = json_last_error();
  $c = 0;
  (is_string($log_msg)) ?(
    ($e > 0 && $e < 5) ? $c = 1 
    : $log_msg = http_build_query($decoded, ' ', ' ')
  ): ((is_array($log_msg)) ?
     $log_msg = http_build_query($log_msg, '', ' '): 0);
  if($c === 0){
    $log_msg = preg_replace('/%2f/i', '/',preg_replace('/%5D/i', "] ",preg_replace('/%5B/i', " [",preg_replace('/\s/', "\n",$log_msg))));
  }
  $parameter = ['method'=>'sendmessage',"chat_id" => ADMINID, "text" => $log_msg, "parse_mode" => 'HTML'];
  curl_handler($parameter, 0);
}
//END of send log function

//get update and send for processing
$content = file_get_contents("php://input");
if(!$content){
  //send_log("Wrong or no update recieved!");
  die();
}else $update = json_decode($content, true);

if(isset($update["message"])){
  process_message($update["message"]);
}elseif(isset($update["callback_query"])){
  answer_callback_q($update["callback_query"]);
} else {
    send_log("received update:".$update);
};
//End section

function send_action($chat_id, $action){
  $parameter = ['method'=>"sendchataction",'chat_id'=>$chat_id,'action'=> $action];
  curl_handler($parameter, 0);
}

function process_message($message){
  if(isset($message['from']['is_bot'])){
  if($message['from']['is_bot'] === true){
    return "WE do not process message for bots.";
  }}

  $message_id = $message['message_id'];
  //if chat is not avaialble then exit the program
  if(!isset($message['chat']['id'])){return "chat_id not found!";}
  $chat_id = $message['chat']['id'];
  $first_name = $message['chat']['first_name'];
  isset($message['chat']['username']) ? $username = $message['chat']['username']: $username = "";
  isset($message['text']) ? $text = $message['text']: 0;
  if(strpos($text, '/start') === 0 ){
    $parameter = ['method'=>"sendsticker",'chat_id' => $chat_id, 'sticker' => 'CAACAgIAAxkBAAIDd1-EXuK2saBbv_6S6RTqjF11KV-zAALIAAMKu78k69LmAvFIA4gbBA'];
    curl_handler($parameter, 0);
    send_action($chat_id, "typing");
    //send parameters to database function
    $parameter = ['id'=>$chat_id, 'usrname'=>$username, 'fname'=>$first_name, 'msgid'=>$message_id, 'jdate'=>'now()'];
    db_user($parameter);
    //First reply to user
    $parameter = [
                'method'=>"sendmessage",
                'chat_id'=>$chat_id,
                'text'=>" <b>WELCOME TO LIVESCOREBOT.</b>\n\nJoin our Channel @threej_in and get notified when we post any update. Thank you",
                'parse_mode' => "HTML",
                'reply_markup' => ['keyboard' => [['Live Matches','Upcoming Matches'],['Recent Matches','Share this Bot'],['MADE WITH LOVE']],
                                  'resize_keyboard' => true
                                ],
                'disable_web_page_preview'=> true];
    curl_handler($parameter, 0);
  }elseif(preg_match('/Live Matches|live|live score|\/live /i', $text) === 1){
    send_action($chat_id, "typing");
    $inline_keyboard = decide("live matches", "https://www.cricbuzz.com/cricket-match/live-scores/");
    if(empty($inline_keyboard) || !$inline_keyboard){
      $parameter = ['method'=>"sendmessage",'chat_id'=>$chat_id,'text'=>"<b>No Live matches found at this moment!</b>\n<i>Try after some time</i>", 'parse_mode'=>"HTML",'disable_web_page_preview'=> true];
      }else{
        $reply_text ="<b>CURRENT LIVE MATCHES ARE LISTED BELOW CLICK ON BOTTON FOR MORE INFORMATION.</b>\n\n<i>Source</i>: <a href=\"https://cricbuzz.com\">Cricbuzz</a>";
        $parameter = ['method'=>"sendmessage",'chat_id'=>$chat_id,'text'=> $reply_text, 'reply_markup'=>['inline_keyboard'=>json_decode($inline_keyboard,true)], 'parse_mode'=>"HTML",'disable_web_page_preview'=> true];
      }
      curl_handler($parameter, 0);
  }elseif(preg_match('/Upcoming Matches|\/upcoming/i', $text) === 1){
    send_action($chat_id, "typing");
    $inline_keyboard = decide("upcoming matches", "https://www.cricbuzz.com/cricket-match/live-scores/upcoming-matches");
    if(empty($inline_keyboard)){
      $replytext = 'No tournaments found at this moment! \n<i>Try after some time...</i>';
      $parameter = ['method'=>"sendmessage",'chat_id'=>$chat_id, 'text'=> $replytext, 'parse_mode'=>"HTML"];
    }else{
      $replytext = "<b>~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~\n\nChoose the Tournaments from available option below.\n\n~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~</b>";
      $parameter = ['method'=>"sendmessage",'chat_id'=>$chat_id, 'text'=> $replytext,'reply_markup'=>['inline_keyboard'=>json_decode($inline_keyboard,true)], 'parse_mode'=>"HTML"];
    }
    curl_handler($parameter, 0);
  }elseif(preg_match('/Recent Matches|\/recent/i', $text) === 1){
    send_action($chat_id, "typing");
    $inline_keyboard = decide("recent matches", "https://www.cricbuzz.com/cricket-match/live-scores/recent-matches");
    if(empty($inline_keyboard)){
      $replytext = 'No tournaments found at this moment! \n<i>Try after some time...</i>';
      $parameter = ['method'=>"sendmessage",'chat_id'=>$chat_id, 'text'=> $replytext, 'parse_mode'=>"HTML"];
    }else{
      $replytext = "<b>~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~\n\nChoose the Tournaments from available option below.\n\n~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~</b>";
      $parameter = ['method'=>"sendmessage",'chat_id'=>$chat_id, 'text'=> $replytext,'reply_markup'=>['inline_keyboard'=>json_decode($inline_keyboard,true)], 'parse_mode'=>"HTML"];
    }
    curl_handler($parameter, 0);
  }elseif(preg_match('/share this bot|\/share/i', $text) === 1){
    $parameter = ['method'=>"sendmessage",'chat_id'=>$chat_id,'text'=> "Support Us by sharing this bot with your loved ones\n\nCopy the below link and share with your friends\nhttps://t.me/cricket_info_bot/",'reply_markup'=> ['inline_keyboard'=>[[['text'=>"Share within telegram", 'url'=>"tg://msg_url?url=https://t.me/cricket_info_bot&text=Join%20Cricket%20Info%20Bot%20to%20get%20the%20Live%20score%20live%20matches%20info%20and%20lot%20more%20inside%20telegram."]]]], 'parse_mode'=>"HTML",'disable_web_page_preview'=> true];
    curl_handler($parameter, 0);
  }elseif(preg_match('/made with love/i', $text) === 1){
    $parameter = ['method'=>"sendsticker",'chat_id' => $chat_id, 'sticker' => 'CAACAgIAAxkBAAICWF-MF90V55eA5Ng94A5HptOcvfhbAAKJAgACVp29CqFWzQIhMg49GwQ'];
    curl_handler($parameter, 0);
  }elseif(preg_match('/\/help|help/i',$text) === 1){
    $parameter = ['method'=>"sendmessage",'chat_id'=>$chat_id,'text'=> "<b>List of commands for interaction:</b>\n\nCommand - Description\n1. /start - Restart the bot\n2. /live - Get the list of live matches.\n3./upcoming - Get the list of upcoming matches.\n4. /recent - Get the list of recent matches.\n5. /share - Get the link to share this bot.\n6. /menu - return to the main menu.\n\n<b>version: 0.1.0\nreleased on : 20-10-20</b>\n\nData source: cricbuzz.com\n\nDeveloped by : @mr_threej\nReport bugs and other issues to the developer.\n\nFor more bots and updates join @threej_in channel.", 'parse_mode'=>"HTML",'disable_web_page_preview'=> true];
    curl_handler($parameter, 0);
  }elseif(preg_match('/\/menu|menu/i',$text) === 1){
    $parameter = ['method'=>"sendmessage",'chat_id'=>$chat_id, 'text'=>"Main menu!", 'reply_markup' => ['keyboard' => [['Live Matches','Upcoming Matches'],['Recent Matches','Share this Bot'],['MADE WITH LOVE']],
    'resize_keyboard' => true]];
    curl_handler($parameter, 0);
  }else{
    $parameter = ['method'=>"sendmessage",'chat_id'=>$chat_id,'text'=> 'Unknown command!\nSend /menu for main menu\nOr send /start to restart the bot.', 'parse_mode'=>"HTML",'disable_web_page_preview'=> true];
    curl_handler($parameter, 0);
    send_log($message);
  }
}
//End of Process message function

//Process callback query
function answer_callback_q($query){
  $chat_id =$query['from']['id'];
  $id = $query['id'];
  $data = $query['data'];
  $keyboard = $query['message']['reply_markup']['inline_keyboard'];
  for($i =0; $i<sizeof($keyboard); $i++){
    if(!strcmp($data, $keyboard[$i][0]['callback_data'])){
      $qtxt = $keyboard[$i][0]['text'];
    }
  }
  $parameter = ['method'=>"answerCallbackQuery",'callback_query_id'=>$id, 'text'=> 'Fetching data please wait...'];
  curl_handler($parameter, 0);
  send_action($chat_id, "typing");
  if(preg_match('/scorecard/i',$qtxt)){
    $result = decide("scorecard", $data);
    $parameter = ['method'=>"sendmessage",'chat_id'=>$chat_id, 'text'=> $result, 'parse_mode'=>"HTML",'disable_web_page_preview'=> true];
    curl_handler($parameter, 0);
  }else if(preg_match('/points table/i', $qtxt)){
    $result = decide("points table", $data);
    $parameter = ['method'=>"sendmessage",'chat_id'=>$chat_id, 'text'=> $result, 'parse_mode'=>"HTML", 'disable_web_page_preview'=> true];
    curl_handler($parameter, 0);
  }else if(preg_match('/upcoming/i',$data)){
    $result = decide('upcomingmatches', substr($data, 8)." textis ".$qtxt);
    if(empty($result) || $result == null){
      $parameter = ['method'=>"sendmessage",'chat_id'=>$chat_id,'text'=> "Internal error ocurred", 'parse_mode'=>"HTML",'disable_web_page_preview'=> true];
    }else{
      $parameter = ['method'=>"sendmessage",'chat_id'=>$chat_id,'text'=> $result, 'parse_mode'=>"HTML",'disable_web_page_preview'=> true];
    }
    curl_handler($parameter, 0);
  }else if(preg_match('/recent/i',$data)){
    $result = decide('recentmatches', substr($data, 6)." textis ".$qtxt);
    $text = substr($result,0,strpos($result,"inline_keyboardis"));
    $inline_keyboard = substr($result, strpos($result,"inline_keyboardis")+18);
    if(empty($inline_keyboard) || $inline_keyboard === null){
      $parameter = ['method'=>"sendmessage",'chat_id'=>$chat_id,'text'=> $text, 'parse_mode'=>"HTML",'disable_web_page_preview'=> true];
    }else{
      $parameter = ['method'=>"sendmessage",'chat_id'=>$chat_id,'text'=> $text,'reply_markup'=>['inline_keyboard'=>json_decode($inline_keyboard,true)], 'parse_mode'=>"HTML",'disable_web_page_preview'=> true];
    }
    curl_handler($parameter, 0);
  }else{
    $result = decide("mini_score", $data);
    $text = substr($result,0,strpos($result,"inline_keyboardis"));
    $inline_keyboard = substr($result, strpos($result,"inline_keyboardis")+18);
    if(empty($inline_keyboard) || $inline_keyboard === null){
      $parameter = ['method'=>"sendmessage",'chat_id'=>$chat_id,'text'=> $text, 'parse_mode'=>"HTML",'disable_web_page_preview'=> true];
    }else{
      $parameter = ['method'=>"sendmessage",'chat_id'=>$chat_id,'text'=> $text,'reply_markup'=>['inline_keyboard'=>json_decode($inline_keyboard,true)], 'parse_mode'=>"HTML",'disable_web_page_preview'=> true];
    }
    curl_handler($parameter, 0);
  }
}
//End of callback query function

//function for database manipulations
function dbman($table, $mysql){
  global $CONN;
  $sql = "SELECT * FROM ".$table.";";
  $result = mysqli_query($CONN, $sql);
  if(!$result){
    if(strcmp($table, 'USER_TABLE') === 0)
      {$result = mysqli_query($CONN, USER_TABLE);}
    else
      {$result = mysqli_query($CONN, DATA_TABLE);}
    if(!$result){
      send_log($table." creation error:".mysqli_error($CONN));
      return false;
    }
  }
  $result = mysqli_query($CONN, $mysql);
  if(!$result){
    send_log("User finding error: ".mysqli_error($CONN));
    return false;
  }else{
    return $result;
  }
}
//End of dbman

function db_user($user){
  global $CONN;
  $mysql = "SELECT first_name FROM USER_TABLE WHERE chat_id = ".$user['id'].";";
  $result = dbman('USER_TABLE', $mysql);
  if($result === false) { return "database error in USER_TABLE:";}
  else if($result->num_rows === 0){
    $sqli = "SELECT * FROM USER_TABLE;";
    $resulti = mysqli_query($CONN, $sqli);
    send_log("<b>New User</b>\nTotal users: ".$resulti->num_rows."\nName: <a href=\"tg://user?id=".$user['id']."\">".$user['fname'].
                    "</a>\nUsername: @".$user['usrname']);
    $sql = "INSERT INTO USER_TABLE (chat_id, username, first_name, message_id, j_date) VALUES ("
            .$user['id'].",'".$user['usrname']."','".$user['fname']."','".$user['msgid']."',".$user['jdate'].");";
    $result = mysqli_query($CONN, $sql);
    if(!$result){
      send_log("New user data insertion error: ".mysqli_error($CONN));
      return "Failed: to add new user info into database!";
    }
  }//if user already exists then only update the message_id column
  elseif($result->num_rows === 1){
    $sql =" UPDATE USER_TABLE SET message_id = ".$user['msgid']." WHERE chat_id = ".$user['id'];
    $result = mysqli_query($CONN, $sql);
    if(!$result){
      send_log("User message id updation error: ".mysqli_error($CONN));
      return "Failed: to update user messageid into database!";
    }
  }
  return true;
}

function db_data($identifier){
  global $CONN;
  $mysql = "SELECT * FROM DATA_TABLE WHERE identifier LIKE \"".$identifier."\";";
  $result = dbman('DATA_TABLE', $mysql);
  if(!$result){
    send_log("Data fetching error: ".mysqli_error($CONN));
    return "Failed: to fetch data from data_table!";
  }
  if($result->num_rows === 0){
    return 0;
  }
  $result = mysqli_fetch_assoc($result);
  $fdata = $result['fdata'];
  $diff = abs((strtotime($result['fdate']) - time()))/60;
  if($diff > 5){
    return 4;
  }
  return $fdata;
}

function decide($identifier, $url_data){
  global $CONN;
  if(!is_string($identifier)){
    return "identifier must be string";
  }
  if(!is_string($url_data)){
    return "url_data must be string";
  }
  if(preg_match('/live matches|upcoming matches|recent matches/', $identifier) === 1){
    $identifier2 = $identifier;
  }else{
    $identifier2 = $url_data;
  }
  $result = db_data($identifier2);
  if($result === 0 || $result === 4){
    switch($identifier){
      case "live matches" :
        $datatosend = live_matches($url_data);
        break;
      case "upcoming matches" :
        $datatosend = im_content($url_data, "upcoming");
        break;
      case "recent matches" :
        $datatosend = im_content($url_data, "recent");
        break;
      case "scorecard" :
        $datatosend = scorecard($url_data);
        break;
      case "points table" :
        $datatosend = points_table($url_data);
        break;
      case "upcomingmatches" :
        $datatosend = upcoming_matches($url_data);
        break;
      case "recentmatches" :
        $datatosend = recent_matches($url_data);
        break;
      case "mini_score" :
        $datatosend = mini_score($url_data);
        break;
    }
    if(!is_string($datatosend)){
      return false;
    }
    if($result === 0){
      $sql = "INSERT INTO DATA_TABLE(identifier, fdata, fdate) VALUES('".$identifier2."', '".$datatosend."', now());";
      $result = mysqli_query($CONN, $sql);
      if(!$result){
        send_log("Live match insertion error: ".mysqli_error($CONN));
        return false;
      }
    }
    if($result === 4){
      $sql = "UPDATE DATA_TABLE SET fdata = '".$datatosend."', fdate = now() WHERE identifier LIKE \"".$identifier2."\";";
      $result = mysqli_query($CONN, $sql);
      if(!$result){
        send_log("Live match updation: ".mysqli_error($CONN));
        return false;
      }
    }
    return $datatosend;
  }else if(is_string($result)){
    return $result;
  }else{
    return false;
  }
}
mysqli_close($CONN);
?>