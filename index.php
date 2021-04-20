<?php

/**
 * Telegram bot script for fetching cricket data from cricapi.com website.
 * 
 * @link http://threej.in
 * @author threej [ Jitendra Pal ]
 * 
 * licence under GNU General Public License v3.0
 * licence @link https://github.com/threej-in/cricket_info_bot/blob/main/LICENSE
 * @version 0.2.0
 */

  //define('ROOT', 'rf/telegram/bots/cricbuzz_bot/');
  define('ROOT', 'rf/tg_bots/cricbuzz_bot/');
  
  //load telegram bot module [ JARVIS ]
  require $threej->req(ROOT."class/functions.php");

  //get update from telegram
  $update = file_get_contents("php://input");

  //log error if wrong update received
  if(empty($update) || !is_string($update)){
    return $jarvis->e(-1, "Wrong update received");
  }

  //send update for processing
  $update_arr = $threej->json__decode($update, 1);
  if(!$update_arr){
    return $jarvis->e(-1, $threej->to_string($update_arr));
  }else{
    $src = $jarvis->get_source($update_arr);
    require $threej->req(ROOT.'msg_processor.php');
  }
  http_response_code(200);
  return ;
?>