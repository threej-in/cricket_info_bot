<?php

require_once "communicator.php";
$COM = new communication; //Initiate new communication with telegram api

require_once "db_man_3j.php";
$db_conn = new db_conn;
$CONN = $db_conn->CONN;

require_once "scrapper.php";

class message{
  public  $from_bot = false,
          $id = NULL,
          $chat_id = NULL,
          $first_name = NULL,
          $username = '',
          $text = '';

  function __construct($message){
    global $COM, $CONN;

    if(!$message){
        $COM->send_log('Wrong or no update received!');
        die();
    }else{
        $update = json_decode($message, true);
    }

    if(isset($update['message'])){
        $this->extract_msg($update['message']);
        if($CONN === NULL || mysqli_errno($CONN) != 0){
          $COM->curl_handler(build_parameter('db error: '.mysqli_error($CONN),$this->chat_id,'Internal error occurred:)'));
          die;
        }
        
        process_message($this);
        
    }elseif(isset($update['callback_query'])){
        answer_callback_q($update['callback_query']);
    }else{
        
        $COM->send_log("received update can't be processed:\n".json_encode($update));
    };
  }

  private function extract_msg($msg){
    $this->id = $msg['message_id'];
    $this->first_name = $msg['chat']['first_name'];

    isset($msg['from']['is_bot'])  && $this->from_bot = $msg['from']['is_bot'];

    isset($msg['chat']['id'])      && $this->chat_id = $msg['chat']['id'];

    isset($msg['chat']['username'])&& $this->username = $msg['chat']['username'];

    isset($msg['text'])            && $this->text = $msg['text'];
  }
}

/**
 * Process message function is for interaction with user here you can setup what commands
 * you bot can understand and replies to, offcourse you can build your own commands
 * and responses.
 * 
 */
function process_message($msg){
    global $COM;
  
    $msg_id = $msg->id;
    $is_bot = $msg->from_bot;
    $first_name = $msg->first_name;
    $chat_id = $msg->chat_id;
    $username = $msg->username;
    $text = $msg->text;
    
    if($chat_id === NULL){
        $COM->send_log("No chatid:".json_encode($msg));
      }
    if($is_bot === true){
        $COM->report_error("message sent by a bot[$chat_id]",$chat_id,'We do not process messages for bot');
        return false;
    }
    
    $COM->send_action($chat_id, 'typing');

    if(strpos($text, '/start') === 0 ){
  
      $COM->curl_handler(build_parameter('sendsticker',$chat_id,'CAACAgIAAxkBAAIDd1-EXuK2saBbv_6S6RTqjF11KV-zAALIAAMKu78k69LmAvFIA4gbBA'));
  
      //GREET USER AND SEND THE MENU BUTTONS FOR INTERACTION
      $reply_text = "<b>WELCOME TO LIVESCOREBOT.</b>\n\nSend /help to see the list of availabel commands and brief info about this bot\n\nJoin our Channel @threej_in and get notified when we post any update. Thank you";
      $ik = [
        'keyboard' => [
          [ 'Live Matches','Upcoming Matches' ],
          [ 'Recent Matches','Share this Bot' ],
          [ 'MADE WITH LOVE' ]
        ],
        'resize_keyboard'=>true
      ];
      $COM->curl_handler(build_parameter('sendmessage', $chat_id, $msg_id, $reply_text, $ik));

      //SEND USER DETAILS TO USER TABLE
      $user = [
        'id'=>$chat_id,
        'usrname'=>$username,
        'fname'=>$first_name,
        'msgid'=>$msg_id,
        'jdate'=>'now()',
        'lu_date'=>'now()'
      ];
      db_user($user);
  
    }elseif(preg_match('/Live Matches|live|live score|\/live /i', $text) === 1){
      
      $inline_keyboard = get_3j('live matches', 'https://www.cricbuzz.com/cricket-match/live-scores/');
      
      if(empty($inline_keyboard) || !$inline_keyboard || strlen($inline_keyboard)<5){
      
        $parameter = build_parameter('sendmessage',$chat_id,$msg_id,"<b>No Live matches found at this moment!</b>\n<i>Try after some time</i>");  
      }else{
  
          $reply_text ="<b>CURRENT LIVE MATCHES ARE LISTED BELOW CHOOSE AN OPTION FOR MORE INFORMATION.</b>\n\n<i>Source</i>: <a href=\"https://cricbuzz.com\">Cricbuzz</a>";
          $parameter = build_parameter('sendmessage', $chat_id,$msg_id, $reply_text, ['inline_keyboard'=>json_decode($inline_keyboard,true)]);
      }
      
      $COM->curl_handler($parameter);
  
    }elseif(preg_match('/Upcoming Matches|\/upcoming/i', $text) === 1){
      
      $inline_keyboard = get_3j('upcoming matches', 'https://www.cricbuzz.com/cricket-match/live-scores/upcoming-matches');
  
      if(empty($inline_keyboard)){
  
        $replytext = 'No tournaments found at this moment! \n<i>Try after some time...</i>';
        $parameter = build_parameter('sendmessage',$chat_id,$msg_id,$replytext);
  
      }else{
        
        $replytext = "<b>~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~\n\nChoose the Tournaments from available option below.\n\n~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~</b>";
        $parameter = build_parameter('sendmessage',$chat_id,$msg_id,$replytext,['inline_keyboard'=>json_decode($inline_keyboard,true)]);
  
      }
      $COM->curl_handler($parameter);
  
    }elseif(preg_match('/Recent Matches|\/recent/i', $text) === 1){
      $inline_keyboard = get_3j('recent matches', 'https://www.cricbuzz.com/cricket-match/live-scores/recent-matches');
      
      if(empty($inline_keyboard)){
  
        $replytext = 'No tournaments found at this moment! \n<i>Try after some time...</i>';
        $parameter = build_parameter('sendmessage',$chat_id,$msg_id,$replytext);
      }else{
        
        $replytext = "<b>~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~\n\nChoose the Tournaments from available option below.\n\n~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~</b>";
        $parameter = build_parameter('sendmessage',$chat_id,$msg_id,$replytext,['inline_keyboard'=>json_decode($inline_keyboard,true)]);
      }
      $COM->curl_handler($parameter);
  
    }elseif(preg_match('/share this bot|\/share/i', $text) === 1){
      
      $reply_text = "Support Us by sharing this bot with your loved ones\n\nCopy the below link and share with your friends\nhttps://t.me/cricket_info_bot/";
      $ik = [
        'inline_keyboard'=>[
          [
            [
              'text'=>"Share within telegram",
              'url'=>"tg://msg_url?url=https://t.me/cricket_info_bot&text=Join%20Cricket%20Info%20Bot%20to%20get%20the%20Live%20score%20live%20matches%20info%20and%20lot%20more%20inside%20telegram."
            ]
          ]
        ]
      ];
      $parameter = build_parameter('sendmessage',$chat_id,$msg_id,$reply_text,$ik);
      $COM->curl_handler($parameter);
  
    }elseif(preg_match('/made with love/i', $text) === 1){
  
      $parameter = build_parameter('sendsticker',$chat_id,$msg_id,'CAACAgIAAxkBAAICWF-MF90V55eA5Ng94A5HptOcvfhbAAKJAgACVp29CqFWzQIhMg49GwQ');
      $COM->curl_handler($parameter);
  
    }elseif(preg_match('/\/help|help/i',$text) === 1){
      
      $reply_text = "<b>List of commands for interaction:</b>\n\nCommand - Description\n1. /start - Restart the bot\n2. /live - Get the list of live matches.\n3./upcoming - Get the list of upcoming matches.\n4. /recent - Get the list of recent matches.\n5. /share - Get the link to share this bot.\n6. /menu - return to the main menu.\n\n<b>version: 0.1.0\nreleased on : 20-10-20</b>\n\nData source: cricbuzz.com\n\nDeveloped by : @mr_threej\nReport bugs and other issues to the developer.\n\nGithub repo: https://github.com/threej-in/cricket_info_bot\n\nFor more bots and updates join @threej_in channel.";
  
      $parameter = build_parameter('sendmessage',$chat_id,$msg_id,$reply_text);
      $COM->curl_handler($parameter);
  
    }elseif(preg_match('/\/menu|menu/i',$text) === 1){
      $ik = [
        'keyboard' => [
          [ 'Live Matches','Upcoming Matches' ],
          [ 'Recent Matches','Share this Bot' ],
          [ 'MADE WITH LOVE' ]
        ],
        'resize_keyboard' => true
      ];
      $COM->curl_handler(build_parameter('sendmessage',$chat_id,$msg_id,'Main menu!', $ik));
    }elseif(strpos($text, '/broadcast') === 0){
      if($chat_id == ADMINID){
        //broadcast(substr($text, 10));
      }
    }else{
      $COM->report_error($msg, $chat_id, 'Unknown command send /help to get the list of command!');  
    }
    if(!(strpos($text, '/start') === 0)){
      $user = [
        'id'=>$chat_id,
        'msgid'=>$msg_id,
        'lu_date'=>'now()'
      ];
      db_user($user);
    }
    return true;
  }
  //End of Process message function

  //Process callback query
function answer_callback_q($query){
  global $COM;
  $chat_id =$query['message']['chat']['id'];
  $id = $query['id'];
  $msg_id = $query['message']['message_id'];
  $data = $query['data'];
  $keyboard = $query['message']['reply_markup']['inline_keyboard'];
  for($i =0; $i<sizeof($keyboard); $i++){
    if(!strcmp($data, $keyboard[$i][0]['callback_data'])){
      $qtxt = $keyboard[$i][0]['text'];
    }
  }
  $parameter = ['method'=>'answerCallbackQuery','callback_query_id'=>$id, 'text'=> 'Fetching data please wait...'];
  $COM->curl_handler($parameter);

  $COM->send_action($chat_id, 'typing');

  if(preg_match('/scorecard/i',$qtxt)){
    $result = get_3j('scorecard', $data);
    $COM->curl_handler(build_parameter('sendmessage',$chat_id,$msg_id,$result));

  }else if(preg_match('/points table/i', $qtxt)){
    $result = get_3j('points table', $data);
    $COM->curl_handler(build_parameter('sendmessage',$chat_id,$msg_id,$result));

  }else if(preg_match('/upcoming/i',$data)){
    $result = get_3j('upcomingmatches', substr($data, 8)." textis ".$qtxt);

    if(empty($result) || $result == null){
      $parameter = build_parameter('sendmessage',$chat_id,$msg_id,"Internal error ocurred");
    }else{
      $parameter = build_parameter('sendmessage',$chat_id,$msg_id,$result);
    }
    $COM->curl_handler($parameter);

  }else if(preg_match('/recent/i',$data)){
    $result = get_3j('recentmatches', substr($data, 6)." textis ".$qtxt);
    $text = substr($result,0,strpos($result,"inline_keyboardis"));
    $inline_keyboard = substr($result, strpos($result,"inline_keyboardis")+18);

    if(empty($inline_keyboard) || $inline_keyboard === null){
      $parameter = build_parameter('sendmessage',$chat_id,$msg_id,$text);
    }else{
      $parameter = build_parameter('sendmessage',$chat_id,$msg_id,$result,['inline_keyboard'=>json_decode($inline_keyboard,true)]);
    }
    $COM->curl_handler($parameter);
  }else{ 
    $result = get_3j('mini_score', $data);
    $text = substr($result,0,strpos($result,"inline_keyboardis"));
    $inline_keyboard = substr($result, strpos($result,"inline_keyboardis")+18);

    if(empty($inline_keyboard) || $inline_keyboard === null){
      $parameter = build_parameter('sendmessage',$chat_id,$msg_id,$text);
    }else{
      $parameter = build_parameter('editmessagetext',$chat_id,$msg_id,$text,$msg_id,['inline_keyboard'=>json_decode($inline_keyboard,true)]);
    }
    $COM->curl_handler($parameter);
    //$parameter = ['method'=>'sendmessage','chat_id'=>$chat_id,'text'=>"<b>unable to process the request!</b>", 'parse_mode'=>'HTML', 'disable_web_page_preview'=> true];
    //curl_handler($parameter, 0);
  }
}
//End of callback query function

function build_parameter($method, $chat_id, $msg_id, $text, $msg_id=null, $ik = false){
  if(strcmp($method, 'sendmessage') === 0){

    if($ik === false){

      return [
        'method'=>$method,
        'chat_id'=>$chat_id,
        'reply_to_message_id'=>$msg_id,
        'text'=>$text,
        'parse_mode' => 'HTML',
        'disable_web_page_preview'=> true
      ];

    }else{
      
      return [
        'method'=>$method,
        'chat_id'=>$chat_id,
        'reply_to_message_id'=>$msg_id,
        'message_id'=> $msg_id,
        'text'=>$text,
        'reply_markup' => $ik,
        'parse_mode' => 'HTML',
        'disable_web_page_preview'=> true
      ];

    }
  }elseif(strcmp($method, 'sendsticker') === 0){
    return [
      'method'=>$method,
      'chat_id' => $chat_id,
      'reply_to_message_id'=>$msg_id,
      'sticker' => $text
    ];
  }
}

function get_3j($identifier, $url_data){
  global $CONN, $COM;
  if(!is_string($identifier)){
    return 'identifier must be string';
  }
  if(!is_string($url_data)){
    return 'url_data must be string';
  }
  if(preg_match('/live matches|upcoming matches|recent matches/', $identifier) === 1){
    $identifier2 = $identifier;
  }else{
    $identifier2 = $url_data;
  }

  $result = db_data($identifier2);
  if($result === 0 || $result === 4){
    switch($identifier){
      case 'live matches' :
        $datatosend = get_live_matches($url_data);
        break;
      case 'upcoming matches' :
        $datatosend = get_intermediate_content($url_data, 'upcoming');
        break;
      case 'recent matches' :
        $datatosend = get_intermediate_content($url_data, 'recent');
        break;
      case 'scorecard' :
        $datatosend = get_scorecard($url_data);
        break;
      case 'points table' :
        $datatosend = get_points_table($url_data);
        break;
      case 'upcomingmatches' :
        $datatosend = get_upcoming_matches($url_data);
        break;
      case 'recentmatches' :
        $datatosend = get_recent_matches($url_data);
        break;
      case 'mini_score' :
        $datatosend = get_mini_score($url_data);
        break;
    }
    if(!is_string($datatosend)){
      return false;
    }
    if($result === 0){
      $sql = "INSERT INTO DATA_TABLE(identifier, fdata, fdate) VALUES('$identifier2', '$datatosend', now());";
      $result = mysqli_query($CONN, $sql);
      if(!$result){
        $COM->send_log("Live match insertion error: ".mysqli_error($CONN));
        return false;
      }
    }
    if($result === 4){
      $sql = "UPDATE DATA_TABLE SET fdata = '$datatosend', fdate = now() WHERE identifier LIKE \"$identifier2\";";
      $result = mysqli_query($CONN, $sql);
      if(!$result){
        $COM->send_log("Live match updation: ".mysqli_error($CONN));
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
