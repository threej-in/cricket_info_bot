<?php

//load corresponding file for dealing with users message
switch($src){
  case 'private':
    require $threej->req(ROOT.'chat/private.php');
    new_private_message($update_arr);
  break;
  case 'threej':
    require $threej->req(ROOT.'chat/threej_tg.php');
    process_threej_msg($update_arr);
    break;
  case 'channel':
  case 'group':
  case 'supergroup':
    require $threej->req(ROOT.'chat/multi_user.php');
    newMsgFromChannel($update_arr);
    break;
  case 'callback_query':
    require $threej->req(ROOT.'chat/callback.php');
    newCallbackQuery($update_arr);
    break;
  case 'inline_query':
    require $threej->req(ROOT.'chat/inline.php');
    newInlineQuery($update_arr);
    break;
  default:
    return $jarvis->e(1,$src.$update_arr);
  break;
}    

