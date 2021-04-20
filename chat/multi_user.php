<?php
/**
 * Function for group chats
 */

function newMsgFromChannel($update_arr){
    global $jarvis;

    //return if recevied update is other then "my_chat_member"
    if(isset($update_arr['my_chat_member']) || isset($update_arr['message'])){}else{return 1;}
    $jarvis->chat_id = $update_arr['message']['chat']['id'] ?? $update_arr['my_chat_member']['chat']['id'] ?? NULL;
    $chat_id = $update_arr['message']['chat']['id'] ?? $update_arr['my_chat_member']['chat']['id'] ?? NULL;

    if(isset($update_arr['message']['chat']['type']) && $update_arr['message']['chat']['type'] === 'channel'){
        $jarvis->leave_chat();
    }
    $botStatus = $update_arr['my_chat_member']['new_chat_member']['status'] ?? NULL;
    if($botStatus === 'kicked' || $botStatus === 'restricted' || $botStatus === 'left'){
        return 1;
    }
    //get userid
    $admin = $update_arr['my_chat_member']['from']['id'] ?? NULL;
    if($admin === 136817688){
        return 1;
    }
    
    //log telegram chat
    $jarvis->logUser($chat_id);
    
    if(isset($update_arr['message']['text'])){
    if($update_arr['message']['text'] === '/help@cricket_info_bot' || $update_arr['message']['text'] === '/start@cricket_info_bot'){
      if($jarvis->send_action('typing')===-1){
          return $jarvis->e(1, 'admin');
      }
      $text = "Cricket info bot works in inline mode.
      
Choose any of the below option. \xF0\x9F\x91\x87";
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
          'text'=>"ok, got it!",
          'callback_data'=>'{"pid":5}'
        ]]
      ]];
      $jarvis->send_message($text, $rm);
    }
  }
    return 1;
}


?>