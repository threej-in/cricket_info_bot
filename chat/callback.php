<?php
//these class extracts neccessary data from callback update data
class callback{
    public 
        $queryId,
        $chatId,
        $msgId,
        $fromId,
        $uid,
        $processId=0;
    
    function __construct($updateArr)
    {
        $this->queryId = $updateArr['id'] ?? NULL; 
        $this->chatId = $updateArr['message']['chat']['id'] ?? NULL;
        $this->msgId = $updateArr['message']['message_id'] ?? NULL;
        $this->fromId = $updateArr['from']['id'] ?? NULL;
        if(isset($updateArr['data']) && gettype($updateArr['data']) === 'string'){
            $data = json_decode($updateArr['data'],true);
            if($data !== NULL){
                $this->processId = $data['pid'] ?? NULL;
                $this->uid = $data['uid'] ?? NULL;
            }
        }
        
    }
}

//handle callback query
function newCallbackQuery($update){
    global $jarvis,$threej;
    $callback = new callback($update['callback_query']);
    $jarvis->chat_id = $callback->chatId == NULL ? $callback->fromId: $callback->chatId;

    if($callback->processId !== NULL){
        $pid = $callback->processId;
        
        if($pid === 1){
            $jarvis->answerCallbackQuery($callback->queryId, 'Choose upcoming format!');
            $text = "Get list of Upcoming matches in every format! Choose your format from below options \xF0\x9F\x91\x87";
            $rm= ['inline_keyboard'=>[
                [[
                    'text'=>"\x0A\xF0\x9F\xA5\x87 In First-class",
                    'switch_inline_query_current_chat'=>'First-class'
                ]],
                [[
                    'text'=>"\x0A\xF0\x9F\x85\xB0\xEF\xB8\x8F In listA",
                    'switch_inline_query_current_chat'=>'listA'
                ]],
                [[
                  'text'=>"\x0A\xE2\x98\x80\xEF\xB8\x8F In ODI",
                  'switch_inline_query_current_chat'=>'ODI'
                ]],
                [[
                    'text'=>"\xF0\x9F\x93\x95 In Tests",
                    'switch_inline_query_current_chat'=>'Tests'
                ]],
                [[
                    'text'=>"In Twenty20",
                    'switch_inline_query_current_chat'=>'Twenty20'
                ]],
                [[
                    'text'=>"\x0A\xE2\x9C\xB3\xEF\xB8\x8F In ANY",
                    'switch_inline_query_current_chat'=>'ANY'
                ]],
                [[
                    'text'=>"\x0A\xE2\x8F\xAA",
                    'callback_data'=>'{"pid":2}'
                ]]
              ]];
            $jarvis->edit_msg($callback->msgId,$text, $rm);
        }elseif($pid ===2){
            $jarvis->answerCallbackQuery($callback->queryId, 'Go Inline');
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
                  'text'=>"\xF0\x9F\x87\xAE\xF0\x9F\x87\xB3 IPL",
                  'callback_data'=>'{"pid":4}'
                ]],
                [[
                    'text'=>"ok, got it!",
                    'callback_data'=>'{"pid":5}'
                ]]
            ]];
            $jarvis->edit_msg($callback->msgId,$text, $rm);
        }elseif($pid===4){
            $jarvis->answerCallbackQuery($callback->queryId, 'Type ipl and your query in inline mode');
            $text = "Get Schedule, All time stats and points table of Indian Premier League.";
            $rm= ['inline_keyboard'=>[
                [[
                    'text'=>"Schedule",
                    'switch_inline_query_current_chat'=>'ipl schedule'
                ],
                [
                    'text'=>"Most Runs",
                    'switch_inline_query_current_chat'=>'ipl most runs'
                ]],
                [[
                  'text'=>"Most Fours",
                  'switch_inline_query_current_chat'=>'ipl most fours'
                ],
                [
                    'text'=>"Most Sixes",
                    'switch_inline_query_current_chat'=>'ipl most sixes'
                ]],
                [[
                    'text'=>"Most Wickets",
                    'switch_inline_query_current_chat'=>'ipl most wickets'
                ],
                [
                    'text'=>"Most Centuries",
                    'switch_inline_query_current_chat'=>'ipl most centuries'
                ]],
                [[
                    'text'=>"Most Half Centuries",
                    'switch_inline_query_current_chat'=>'ipl most fifties'
                ],
                [
                    'text'=>"Highest Score",
                    'switch_inline_query_current_chat'=>'ipl highest score'
                ]],
                [[
                    'text'=>"Fastest Century",
                    'switch_inline_query_current_chat'=>'ipl fastest century'
                ],
                [
                    'text'=>"Fastest Fifty",
                    'switch_inline_query_current_chat'=>'ipl fastest fifty'
                ]],
                [[
                    'text'=>"Points Table",
                    'switch_inline_query_current_chat'=>'ipl pointstable'
                ],
                [
                    'text'=>"\x0A\xE2\x8F\xAA",
                    'callback_data'=>'{"pid":2}'
                ]]
              ]];
            $jarvis->edit_msg($callback->msgId,$text, $rm);
        }elseif($pid === 5){
            $jarvis->answerCallbackQuery($callback->queryId, 'Send /help | /help@cricket_info_bot if you need me again');
            $jarvis->delete_msg($callback->msgId);
        }
    }else{
        file_put_contents(ROOT.'temp.txt',print_r($update, true));
        return $jarvis->answerCallbackQuery($callback->queryId, 'You have clicked on outdated message.');
    }
}

?>