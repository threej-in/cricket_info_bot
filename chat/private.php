<?php
/**
 * This file handles message from private chats
 * @package oneEyedBot
 * @author threej[Jitendra Pal]
 * @version 0.1.0
 * 
*/

class private_message{
  public  $from_bot = false,
    $msg_id = NULL,
    $chat_id = NULL,
    $chat_fname,
    $chat_lname,
    $chat_usrname,
    $chat_type,
    $chat_title,
    $user_id,
    $fromUsername,
    $user_lname,
    $user_usrname,
    $user_lang_code,
    $text = '',
    $reply_msgid = NULL,
    $command = NULL,
    $fwd_msg_chatid=NULL;

  function __construct($update_arr){
    isset($update_arr['message']) ?
      $this->extract_message($update_arr['message']) : 0
    ;
  }

  //extract relevant data from received update.
  private function extract_message($msg){
    
    $this->msg_id = $msg['message_id'];
    $chat = $msg['chat'];
    $user = $msg['from'];

    /* Note here '??' is equivalent to isset function. If specified array key is not found 
      then NULL will be assigned to the variable*/
    $this->chat_id = $chat['id'] ?? NULL;
    $this->chat_type = $chat['type'] ?? NULL;
    $this->chat_title = $chat['title'] ?? NULL;
    $this->chat_fname = $chat['first_name'] ?? NULL;
    $this->chat_lname = $chat['last_name'] ?? NULL;
    $this->chat_usrname = $chat['username'] ?? NULL;
    
    $this->user_id = $user['id'] ?? NULL;
    $this->from_bot = $user['is_bot'] ?? NULL;
    $this->fromUsername = $user['first_name'] ?? NULL;
    $this->user_lname = $user['last_name'] ?? NULL;
    $this->user_usrname = $user['username'] ?? NULL;
    $this->user_lang_code = $user['language_code'] ?? NULL;

    $this->text = $msg['text'] ?? '';

    //reply message extract
    $this->reply_msgid = $msg['reply_to_message']['message_id'] ?? NULL;
    //forwarded message
    if(isset($msg['forward_from']['is_bot']) && $msg['forward_from']['is_bot']){
      //$this->fwd_msg_chatid = $msg['forward_from']['id']?? NULL;
      $this->fwd_msg_chatid = NULL;
    }elseif(isset($msg['entities'][0]['type']) && $msg['entities'][0]['type'] === 'mention'){
      $this->fwd_msg_chatid = substr($msg['text'], 0, $msg['entities'][0]['length']);
    }else{
      $this->fwd_msg_chatid = $msg['forward_from_chat']['id']?? NULL;
    }
    

    //service message extract
    isset($msg['pinned_message']) ? $this->text = 'service_message_jfgbsbgiojbrovjgber' :  0 ;

  }
}

/**
 * process private messages
 * 
 * @param array $update_arr - decoded update array
 * @return bool
 */
function new_private_message($update_arr){
  global $jarvis, $cricdb;

  $msg = new private_message($update_arr);
  //log user
  if($msg->chat_id === NULL){
    if(isset($update_arr['my_chat_member']['chat']['id'])){
      return 1;
    }
    return $jarvis->e(-1, $update_arr);
    
  }
  $jarvis->loguser($msg->chat_id);
  
  if($msg->msg_id !== NULL){
    $jarvis->msg_id = $msg->msg_id;
  }
  
  $rm= ['inline_keyboard'=>[
    [[
      'text'=>"\xF0\x9F\xA5\x8E Upcoming Matches",
      'callback_data'=>'{"pid":1}'
    ]],
    [[
      'text'=>"\xF0\x9F\x8E\xBE Recent Matches",
      'switch_inline_query_current_chat'=>'recent'
    ]],
    [[
      'text'=>"\xF0\x9F\x93\x86 Cricket calendar",
      'switch_inline_query_current_chat'=>'calendar'
    ]],
    [[
      'text'=>"\xE2\x9B\xB9\xEF\xB8\x8F Get player detail in current chat",
      'switch_inline_query_current_chat'=>'player sachin tendulkar'
    ]],
    [[
      'text'=>"\xE2\x9B\xB9\xEF\xB8\x8F Get player detail in other chat",
      'switch_inline_query'=>'player sachin tendulkar'
    ]],
    [[
      'text'=>"\xF0\x9F\x87\xAE\xF0\x9F\x87\xB3 IPL",
      'callback_data'=>'{"pid":4}'
    ]],
    [[
      'text'=>"ok, got it!",
      'callback_data'=>'{"pid":5}'
    ]]
  ]];

  if(strpos($msg->text, '/start') === 0 ){
    $jarvis->send_action('typing');
    $jarvis->send_sticker('CAACAgIAAxkBAAIDd1-EXuK2saBbv_6S6RTqjF11KV-zAALIAAMKu78k69LmAvFIA4gbBA');

    $reply_text = "\xF0\x9F\x91\x8B Hello ".$msg->chat_fname.", I am a telegram bot and I will provide you with information from cricket world.
  
Click on any of the below provided button. \xF0\x9F\x91\x87";
    $jarvis->send_message($reply_text, $rm);

  }elseif(preg_match('/share this bot|\/share/i', $msg->text) === 1){
    $jarvis->send_action('typing');
    $reply_text = "Support Us by sharing this bot with your \xF0\x9F\x92\x96 loved ones

Copy the below link and share with your friends
https://t.me/cricket_info_bot/";
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
    $jarvis->send_message($reply_text, $ik);

  }elseif(preg_match('/\/help|help/i',$msg->text) === 1){
    $jarvis->send_action('typing');
    $text = "Cricket info bot works in inline mode.
    
Choose any of the below option. \xF0\x9F\x91\x87";
    $jarvis->send_message($text, $rm);

  }elseif(strpos($msg->text, '/broadcast') === 0){
    if($msg->chat_id === ADMINID){
      //message for broadcasting
      
    }
  }elseif(strpos($msg->text, '/removeUnactiveUsers') === 0){
    if($msg->chat_id === ADMINID){
      $users=0;
      $offset = intval(substr($msg->text, strpos($msg->text,' ')+1));
      $sql = 'SELECT CHAT_ID FROM TGUSER_TABLE_3J WHERE ID > ? LIMIT 25;';
      
      $arr = [
        [&$offset,'i']
      ];
      if($cricdb->prepare($sql, $arr) === -1){return $jarvis->e(-1, $cricdb->error);}
      do{
        $result = $cricdb->fetch();
        
        if(isset($result['CHAT_ID'])){
          $chat = $result['CHAT_ID'];
          $jarvis->chat_id = $chat;
          $curl_result = $jarvis->send_action('typing');
          if($curl_result === -1){
            $sql = 'DELETE FROM TGUSER_TABLE_3J WHERE CHAT_ID = '.$chat.';';
            if($cricdb->query($sql)){$users++;}
          }
          
        }
        
      }while($result != 0);
      $jarvis->send_log($users." chats removed!");
    }
  }else{
    $text = "cricket info bot works in inline mode, send /help for more detail...";
    $jarvis->send_message($text, "", true);
    return 1;
  }
  return true;
}
//End of Process message function