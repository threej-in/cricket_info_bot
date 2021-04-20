<?php
/**
 * TGBOTMODULE - Easy to use module for available methods in telegram bot api
 * 
 * @package tgbotmodule
 * @author threej [Jitendra Pal]
 * @version 0.1.0
*/

require $threej->req(ROOT.'class/config_3j.php');
require $threej->req(ROOT.'class/db_man_3j.php');

/**
 * Class contains easy to use function for accessing the available methods in Telegram bot api
 * @param string $token Bot token received from bot father
 * 
 */
class jarvis_functions{
    private 
        $token;

    public 
        $chat_id=NULL,
        $user_id,
        $update_id,
        $msg_id=NULL,
        $PMHTML,
        $curl_err,
        $error,
        $islogged;

    /**
     * Initializes chat_array and msg_array
     * @param int $chat_id chat_id received in the update
     * @param int $msg_id msg_id received in the update
     * 
     */
    function __construct($token){
        $this->token = $token;
        $this->PMHTML = ['parse_mode'=>'HTML'];
        $this->islogged = 0;

        if(empty($this->token)){
            $this->e(0,'Bot token is empty');
            die;
        }
        define('API_URL', "https://api.telegram.org/bot".$this->token."/");
        define('FILE_URL', "https://api.telegram.org/file/bot".$this->token."/");
    }
    
    /**
     * curl_handler function handles request and response from and to the telegram api
     * @param array $parameter parameters array as specified in telegram bots api documents
     * @return -1|string json string or boolean
     * -1 if error occured else json string 
     */
    private function curl_handler($parameter, $URL = API_URL){

        empty($parameter) && $parameter = array(); //declare an empty array if $parameter is empty
        
        if(!is_array($parameter)){
            return  $this->e(-1, "Parameters must be an array\n");
        }

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL=>$URL,
            CURLOPT_RETURNTRANSFER=>true,
            CURLOPT_POST=>TRUE,
            CURLOPT_POSTFIELDS=>json_encode($parameter),
            CURLOPT_HTTPHEADER=>array('Content-Type: application/json'),
            CURLOPT_CONNECTTIMEOUT=>10,
            CURLOPT_TIMEOUT=>10
        ]);

        $response = curl_exec($ch);
    
        $this->curl_err['server_code'] = $server_r_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->curl_err['no'] = $err_no = intval(curl_errno($ch));
        $this->curl_err['description'] = curl_error($ch);
        
        if($err_no !== 0 || $server_r_code !== 200) {
            $result = json_decode($response,true);
            //log and return the error response received from telegram api
            if(!empty(curl_error($ch))){
                $this->error = curl_error($ch);
                return $this->e(-1,NULL, NULL);
            }elseif(!empty($result['description'])){
                $this->error = $result['description'];
                return $this->e(-1,NULL, NULL);
            }
            return $response;
        }
        
        curl_close($ch);
        
        return $response;

    }
    //End of curl_handler method
    
    public function answerCallbackQuery($CBQueryId, $msg, $showAlert = false)
    {
        if(empty($CBQueryId) || $CBQueryId === NULL){return -1;}
        return $this->execute([
            'method' => 'answerCallbackQuery',
            'callback_query_id' =>$CBQueryId,
            'text'=> $msg,
            'show_alert' => $showAlert
        ], false);
    }

    public function answerInlineQuery($qid, $data, $next_offset='', $time = 300, $switch_text = 'Cricket Info Bot',
        $is_personal = false, $deeparam = 'inline'){
        return $this->execute([
            'method' => 'answerInlineQuery',
            'inline_query_id' => $qid,
            'results' => $data,
            'cache_time'=> $time,
            'is_personal' => $is_personal,
            'next_offset' => $next_offset,
            'switch_pm_text'=> $switch_text,
            'switch_pm_parameter' => $deeparam
        ], false);
    }
    
    /** 
     * Deletes existing message sent by bot in private chat and groups if have proper permission.
     * 
     * @return array|bool - Curl response as array if DEBUGMODE is set true, else bool
     */
    public function delete_msg($delete_msg_id){
        
        return $this->execute([
            'method'=>'deletemessage',
            'message_id'=>$delete_msg_id
        ]);
    }
     /**
     * Decide where to print the error.
     * 
     * @param string $err_msg Error message which will be shown to end user
     * @return bool
    */
    public function e($return_code, $admin_error, $msg_for_user = 'Internal error occured!'){

        $dt = array_reverse(debug_backtrace(0));
        $errloc = '';
        foreach($dt as $i){
            $errloc .= '['.basename($i['file']).':'.$i['line'].']';
        }
        //error log to the root file
        if(!empty($this->error) || !empty($admin_error)){
            $error = empty($this->error) ? $admin_error : $this->error;
            $error = $this->to_string($error);
            error_log("\r\n[".date("H:i:s d/m/Y",time())."]$errloc$ ".$error,3,__DIR__."/../site_error.log");
        }
        
        if(DEBUG_MODE === true){
            echo "<pre>$errloc";
            print_r($admin_error);
            echo '</pre>';
        }else{
            if(!empty(ADMINID) && !empty($admin_error) && $admin_error !== NULL){
                $admin_error = $this->to_string($admin_error);
                $this->send_log($admin_error);
            }
            if(!empty($this->chat_id) && !empty($msg_for_user) && $msg_for_user !== NULL){
                $msg_for_user = $this->to_string($msg_for_user);
                $this->send_message($msg_for_user);
            }
        }
        
        return $return_code;
    }    
    /**
     * Edit messages sent by the bot.
     * 
     * @param string $text -replacing text
     * @param array $reply_markup -array of inline keyboard as 
     * ['inline_keyboard'=>[[[text & (url | login_url | callback_data |
     *  switch_inline_query | switch_inline_query_current_chat | callback_game | pay)]]]
     * ]
     * @param string $inline_msg_id
     * @param bool $disable_web_preview
     * 
     * @return array|bool - Curl response as array if DEBUGMODE is set true, else bool
     */
    public function edit_msg(
        $msgId,
        $text,
        $reply_markup=NULL,
        $inline_msg_id=NULL,
        $disable_web_preview=true)
    {

        $result = $this->execute([
            'method'=>'editmessagetext',
            'message_id'=>$msgId,
            'text'=>$text,
            'reply_markup'=> $reply_markup,
            'inline_message_id'=>$inline_msg_id,
            'disable_webpage_preview'=> $disable_web_preview
        ]+$this->PMHTML);
        if($result === -1){
            $this->send_message('can\'t edit old messages');
        }
        return $result;
    }
    /**
     * Edit message reply markup
     * 
     * @param array $reply_markup -array of inline keyboard as 
     * ['inline_keyboard'=>[[[text & (url | login_url | callback_data |
     *  switch_inline_query | switch_inline_query_current_chat | callback_game | pay)]]]
     * ]
     * @param string $inline_msg_id
     * 
     * @return -1|1
     */
    public function editMsgReplyMarkup(
        $msgId,
        $reply_markup=null,
        $inline_msg_id=null)
    {

        return $this->execute([
            'method'=>'editMessageReplyMarkup',
            'message_id'=>$msgId,
            'reply_markup'=> $reply_markup,
            'inline_message_id'=>$inline_msg_id,
        ]);
    }

    /**
     * Edit message caption of message sent by bot.
     * 
     * @param string $caption -replacing text
     * @param array $reply_markup -array of inline keyboard as 
     * ['inline_keyboard'=>[[[text & (url | login_url | callback_data |
     *  switch_inline_query | switch_inline_query_current_chat | callback_game | pay)]]]
     * ]
     * @param string $inline_msg_id
     * 
     * @return array|bool - Curl response as array if DEBUGMODE is set true, else bool
     * 
     */
    public function edit_msg_caption(
        $caption,
        $reply_markup=null,
        $inline_msg_id=null)
    {
        return $this->execute([
            'method'=>'editmessagecaption',
            'caption'=>substr($caption, 0,1022),
            'reply_markup'=> $reply_markup,
            'inline_message_id'=>$inline_msg_id,
        ] + $this->PMHTML);
    }
    /**
     * Executes curl_handler function 
     * @param array $parameter array of parameter as specified in the telegram api
     * @param bool $is_chat_id_req whether to include CHATARR or not
     * @return string|bool - returns Curl response as json string or boolean value
     */
    public function execute($parameter, $is_chat_id_req = true){
        if($is_chat_id_req === true){
            if($this->chat_id !== NULL && !empty($this->chat_id)){
                $parameter += ['chat_id'=>$this->chat_id];
            }else{
                return $this->e(-1, 'chat id not specified');
            }
        }
        $result = $this->curl_handler($parameter);
        if($result === -1){
            return -1;
        }
        return json_decode($result,true);
    }

    /**
     * forward message
     * @param int|string $from_chat
     * @param bool $send_notification
     * @return array|bool - Curl response as array if DEBUGMODE is set true, else bool
     */
    public function forward_msg($from_chat, $send_notification = true){
        
        return $this->execute([
            'method'=>'forwardmessage',
            'from_chat_id'=>$from_chat,
            'disable_notification'=>!($send_notification)
        ]);
    }

    /**
     * forward copied message
     * @param int|string $from_chat
     * @param bool $send_notification
     * @return array|bool - Curl response as array if DEBUGMODE is set true, else bool
     */
    public function forward_copied_msg(
        $from_chat,
        $src_msg_id,
        $caption = null,
        $reply_markup = "",
        $reply_to_original_msg = false,
        $send_notification = true
        ){
        return $this->execute([
            'method'=>'copymessage',
            'from_chat_id'=>$from_chat,
            'message_id'=>$src_msg_id,
            'capiton'=>$caption,
            'reply_to_message_id'=>$reply_to_original_msg == true && MSGARR['message_id'],
            'allow_sending_without_reply'=>true,
            'disable_notification'=>!($send_notification),
            'reply_markup'=>$reply_markup
        ] + $this->PMHTML);
    }
    public function getAdminList()
    {
        return $this->execute([
            'method'=> 'getChatAdministrators'
        ]);

    }
    public function getChat($chat_id = NULL){
        if($chat_id === NULL){
            if(gettype($this->chat_id) === 'string'){
                $this->chat_id = '@'.$this->chat_id;
            }

            return $this->execute([
                'method'=> 'getChat'
            ]);
        }
        if(gettype($chat_id) === 'string'){
            $chat_id = '@'.$chat_id;
        }
        return $this->execute([
            'method'=> 'getChat',
            'chat_id'=>"$chat_id"
        ],false);

    }
    /**
     * Get chat member details from a group/channel
     * @param double $user_id
     * @param double $chat_id
     */
    public function getChatMember($user_id, $chat_id = NULL){
        if($chat_id === NULL){
            return $this->execute([
                'method'=>'getChatMember',
                'user_id'=>$user_id]);
        }
        return $this->execute([
            'method'=>'getChatMember',
            'user_id'=>$user_id,
            'chat_id'=>$chat_id
        ],false);
    }
    /**
     * get jpeg photo
     * @param string $file_id Unique file Id received from getFile method
     * @return -1|string 
     * - -1 on error
     * - base64 string on success
     */
    public function getPhoto($file_id){
        $param =[
            'method'=>'getFile',
            'file_id'=> $file_id
        ];
        $result = $this->execute($param);
        if(!isset($result['ok'])){
            return -1;
        }else{
            if($result['ok']){
                $img = base64_encode(file_get_contents(FILE_URL.$result['result']['file_path']));
                $image = "data:image/jpeg;base64,".$img;
            }else{
                $image = $result['description'];
            }
        }
        return $image;
    }

    /**
     * Get source of received update
     * @param array $update json decoded array of update received from telegram webhook
     * @return string returns the type of content received.
     */
    public function get_source($update){

        $type = $update['message']['chat']['type'] ??
        $update['edited_message']['chat']['type'] ??
        $update['channel_post']['chat']['type'] ??
        $update['edited_channel_post']['chat']['type'] ??
        $update['my_chat_member']['chat']['type'] ?? false;

        if(!$type){
            if(isset($update['callback_query'])){return "callback_query";}
            if(isset($update['inline_query'])){return "inline_query";}
        }

        return $type;
    
        
        if(isset($update['chosen_inline_result'])){return "chosen_inline_result";}
        
        if(isset($update['shipping_query'])){return "shipping_query";}
        if(isset($update['pre_checkout_query'])){return "pre_checkout_query";}
        if(isset($update['poll'])){return "poll";}
        if(isset($update['poll_answer'])){return "poll_answer";}
        die;
    }

    public function getSubsCount($chat_id = NULL){
        if($chat_id === NULL){
            return $this->execute([
                'method'=> 'getChatMembersCount'
            ]);
        }else{
            return $this->execute([
                'method'=> 'getChatMembersCount',
                'chat_id'=>$chat_id
            ],false);
        }
    }
  
    public function getUserProfilePhotos($offset=0, $limit=100){
        $param =[
            'method'=>'getUserProfilePhotos',
            'user_id'=>$this->user_id,
            'offset'=>intval($offset),
            'limit'=>intval($limit)
        ];
        $result = $this->execute($param,false);
        if(!isset($result['ok'])) return $this->e(-1, $this->curl_err);
        if($result['ok']){
            $images_arr = [];
            $result = $result['result']['photos'];
            foreach($result as $i => $file_id){
                $file = $file_id[0]['file_id'];
                $img = $this->getPhoto($file);
                if($img === -1){
                    $images_arr[$i] = "not found";
                }else{
                    $images_arr[$i] = $img;
                }
            }
            return $images_arr;
        }
        return $this->e(-1,$result['description']);
        
    }

    /**
     * Fetches update from telegram api. Only for development purpose. Debug mode must be set to true
     * @param int $limit
     * @param int $timeout
     * @param int $offset
     */
    public function get_updates($offset = 0,$limit = 100, $timeout = 3600){
        if(!is_int($limit) || !is_int($timeout) || !is_int($offset)){return false;}
        $link = API_URL."getupdates";
        $link = $link.'?limit='.$limit.'&timeout='.$timeout.'&offset='.$offset;        
        if(!$result = file_get_contents($link)){
            return $this->e(-1,'threej_functions::Delete webhook to get_updates');
        }
        return json_decode($result,true);
    }

    /** 
     * bot will leave the chat
     * @return array|bool - Curl response as array if DEBUGMODE is set true, else bool
     */
    public function leaveChat($chat_id = NULL){
        if($chat_id != NULL){
            return $this->execute([
                'method'=> 'leavechat',
                'chat_id' => $chat_id
            ],false);
        }else{
            return $this->execute([
                'method'=> 'leavechat',
            ]);
        }
    }
    public function logUser($user_id){
        global $cricdb;
        $this->chat_id = $user_id;
        $isRegistered = $cricdb->check_data(USER_TABLE_NAME, USERID_COL_NAME, $user_id, 'd');
        if($isRegistered === 1){
            $this->islogged = 1;
            return 1;
        }elseif($isRegistered === 0){
            if($this->newUser() !== -1){
                $this->islogged = 1;
                return 1;
            }
            return -1;
        }
        return -1;
    }
    public function newUser(){
        global $cricdb;
        if($this->chat_id === NULL){return $this->e(-1,'Chatid required');}
        $result = $this->getChat();
        if(isset($result['ok']) && $result['ok']){
            $fn = $result['result']['first_name'] ?? '';
            $un = $result['result']['username'] ?? '';
            $date = date('Y-m-d H:i:s', time());
        }else{
            return $this->e(-1, $result);
        }
        
        $sql = 'INSERT INTO TGUSER_TABLE_3J(CHAT_ID, USERNAME, FIRST_NAME, J_DATE) VALUES(?,?,?,?);';
        $arr = [
            [&$this->chat_id,'d'],
            [&$un,'s'],
            [&$fn,'s'],
            [&$date,'s']
        ];
        
        if($cricdb->prepare($sql, $arr) === -1){return -1;}
        $cricdb->query("SELECT COUNT(*) AS TOTAL FROM TGUSER_TABLE_3J;");
        $data = $cricdb->fetch_data();
        if(empty($fn)) $fn = 'user';
        $text = "New chat: <a href=\"tg://user?id=$this->chat_id\">$fn</a>\n@$un\nTotal: ".($data[0]??0);
        $this->send_log($text);
        return 1;
    }

    /** 
     * Pin message
     * @param int $pin_msg_id Message Id to be pinned
     * @param bool $send_notification
     * @return array|bool - Curl response as array if DEBUGMODE is set true, else bool
     */
    public function pinMsg($pin_msg_id ,$send_notification = true){

        return $this->execute([
            'method'=>'pinchatmessage',
            'message_id'=>$pin_msg_id,
            'disable_notification'=>!($send_notification)
        ]);
    }

    /** 
     * Removes the menu keyboard/button
     * @param string $msgtouser - Indicate user that you have removed the menu buttons.
     * @param bool $selective - Buttons will be removed for selective user, useful in the group
     * @return array|bool - Curl response as array if DEBUGMODE is set true, else bool
     */
    public function remove_keyboard(
        $msgtouser,
        $selective = false
        ){
        return $this->send_message($msgtouser,[
            'remove_keyboard'=>true,
            'selective'=> $selective
        ]);
    }

    /**
     * Actions notify user about bot's current status
     *
     * @param string $action - Supported actions (typing | upload_photo | record_video | upload_video
     * | record_audio | upload_audio | upload_document | find_location | record_video_note | record_audio_note)
     * @return array|bool - Curl response as array if DEBUGMODE is set true, else bool
     */
    public function send_action($action = "typing"){
        
        $result = $this->execute([
            'method'=>'sendchataction',
            'action'=> $action
        ]);
        if($result === -1){
            die;
        }
        return $result;
    }

    /**
     * Send error logs to admin
     * @param mixed $msg log message which is sent to admin.
     * @return bool true if message sent successfully else false
     */
    public function send_log($msg, $replace_space_with_newline = 0){
        if(!empty(ADMINID)){

            if($replace_space_with_newline === 1){
                $msg = preg_replace('/ /',"\n", $this->to_string($msg));
            }else{
                $msg = preg_replace('/\s+ /',"", $this->to_string($msg));
            }
            if(empty($msg)){
                $this->error = 'send_log func : msg is empty';
                return false;
            }

            $parameter = [
                'method'=>'sendmessage',
                'chat_id' => ADMINID,
                'text' => $msg
            ] + $this->PMHTML;
            return $this->curl_handler($parameter);
        }
        $this->error = 'ADMINID not set.';
        return false;
    }
    
    /** 
     * sends text message with reply_markup
     * @param string $text -text message to send
     * @param array $markup_parameter
     * Array of
     * *
     * * ReplyKeyboardMarkup as 
     * * * ['keyboard'=>[
     * * * * [['text'=>'string'],['text'=>'','request_contact'=>bool]],
     * * * * [['text'=>'','request_location'=>bool]],
     * * * * [['text'=>'','request_poll'=>['type'=>'quiz']]]],
     * * * 'resize_keyboard'=>bool,
     * * * 'selective'=>bool
     * * * ]
     * *
     * * InlineKeyboardMarkup as 
     * * * ['inline_keyboard'=>
     * * * * [[[ 'text'=>'string' & (url | login_url | callback_data |
     * switch_inline_query | switch_inline_query_current_chat | callback_game | pay)]]
     * * * * ]
     * * * ]
     * *
     * * ReplyKeyboardRemove as ['remove_keyboard'=>'true','selective'=>bool]
     * *
     * * ForceReply as ['force_reply'=>bool, 'selective'=>bool]
     * *
     * @param int $reply_msgid - Reply to message ID
     * @param bool $web_preview - Disable web page preview
     * @param bool $send_notification - send notification
     * @param bool $allow_wo_replymsg - Pass True, if the message should be sent even if the specified replied-to message is not found
     *
     * @return array|bool - Curl response as array if DEBUGMODE is set true, else bool
     */
    public function send_message(
        $text,
        $markup_parameter = "",
        $reply_msgid = true,
        $noweb_preview = true,
        $send_notification = true,
        $allow_wo_replymsg = true
        ){
        if(gettype($text) !== 'string'){
            return $this->e(-1, 'Message must be a string.');
        }
        if(strlen(strip_tags($text)) >4040){
            return $this->e(-1, 'Message is too long.');
        }
        return $this->execute([
            'method'=>'sendMessage',
            'text'=> $text,
            'reply_markup'=> $markup_parameter,
            'reply_to_message_id'=> $reply_msgid ? $this->msg_id: null,
            'disable_web_page_preview'=>$noweb_preview,
            'disable_notification'=>!$send_notification,
            'allow_sending_without_reply'=> $allow_wo_replymsg
        ] + $this->PMHTML);
    }

    public function sendPhoto($photoUniqueId, $caption='')
    {
        return $this->execute([
            'method' => 'sendPhoto',
            'photo' => strval($photoUniqueId),
            'caption' => $caption
        ] + $this->PMHTML);
    }
    
    /** 
     * Send sticker if allowed
     * @param string $file_id
     * @return array|bool - Curl response as array if DEBUGMODE is set true, else bool
     */
    public function send_sticker($file_id){

        return $this->execute([
            'method' => 'sendsticker',
            'sticker' => $file_id
        ]);
    }

    /** 
     * Set bot commands
     * @param array $param Array consisting list of bot command as key and its description as value
     * @return array|bool - Curl response as array if DEBUGMODE is set true, else bool
     */
    public function set_commands($parameter){
        $botcommand = [];
        $i = 0;
        foreach($parameter as $k => $v){
            $botcommand[$i++] = [
                'command'=>$k,
                'description'=>$v
            ];
        }

        return $this->execute([
            'method'=>'setmycommands',
            'commands'=>$botcommand
        ], false);
    }

      /**
     * Converts any data type to string value
     * @param mixed $data
     * @return string
     */
    public function to_string($data){
        if(is_string($data)){

            $decoded = json_decode($data, true);
            $err = json_last_error();
            //if json_decode function decodes the string successfully then build the http query.
            if(($err > 0 && $err < 5) && ( $decoded !== false && $decoded !== NULL ) ){
                $data = http_build_query($decoded, ' ', ' ');
            }
            
        }elseif(is_array($data) || is_object($data)){
        
            $data = http_build_query($data, '', ' ');
        }else{
            if($data == null){
                $data = json_encode(debug_backtrace(0));
            }else{
                $data = strval($data);
            }
        }

        //string cleaning for more readability
        $find = ['/%2f/i','/%5D/i', '/%5B/i', '/\s+/'];
        $replace = ['/', ']', '[', ' '];
        $result = preg_replace($find,$replace, $data);
        return $result;
    }


    /**
     * Unpin message function
     * @param int $unpin_msg_id
     * @return array|bool - Curl response as array if DEBUGMODE is set true, else bool
     */
    public function unpin_msg($unpin_msg_id){

        return $this->execute([
            'method'=>'unpinchatmessage',
            'message_id'=>$unpin_msg_id
        ]);
    }

    /**
     * Unpin all messages, It is recommended to confirm once with user before executing this method
     * @return array|bool - Curl response as array if DEBUGMODE is set true, else bool
     */
    public function unpin_all_msg(){

        return $this->execute([
            'method'=>'unpinallchatmessages',
        ]);
    }
}

$jarvis = new jarvis_functions($BOT_TOKEN);
$cricdb = new dbRules;
unset($BOT_TOKEN);