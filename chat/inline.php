<?php
    class inlineQuery{
        public $queryId,
            $query;
        public function __construct($update_arr) {
            global $jarvis;
            $query = $update_arr['inline_query'] ?? NULL;
            if($query === NULL){
                $jarvis->e(-1 , $update_arr);
            }
            $this->queryId = $query['id']?? NULL;
            $this->query = $query['query']?? NULL;
        }
    }

    require $threej->req(ROOT.'class/handleApiData.php');
    //respond to the current inline query
    function newInlineQuery($update_arr){
        global $jarvis,$cricdb,$threej;
        $iq = new inlineQuery($update_arr);
        if($iq->queryId === NULL || $iq->query === NULL){
            $jarvis->e(-1 , $update_arr);
        }
        $query = substr($iq->query, 0, strpos($iq->query, ' '));
        if(empty($query)){$query = $iq->query;}
        switch($query){
            case 'calendar':
                calendar($iq->queryId);
            break;
            case 'recent':
            case 'Recent':
                recent_match($iq->queryId);
            break;
            case 'ANY':
            case 'any':
                upcoming_match($iq->queryId, 'ANY');
            break;
            case 'First-class':
            case 'firstclass':
                upcoming_match($iq->queryId, 'First-class');
            break;
            case 'listA':
                upcoming_match($iq->queryId, 'listA');
            break;
            case 'Tests':
                upcoming_match($iq->queryId, 'Tests');
            break;
            case 'Twenty20':
            case 'T20':
                upcoming_match($iq->queryId, 'Twenty20');
            break;
            case 'ODI':
            case 'odi':
                upcoming_match($iq->queryId, 'ODI');
            break;
            case 'player':
                $playerName =substr($iq->query, (strpos($iq->query, ' ')+1));
                search_player($playerName, $iq->queryId);
            break;
            
            default:
                
                return $jarvis->answerInlineQuery($iq->queryId, [[
                    'type'=>'article',
                    'id'=>'1',
                    'title'=>'No Result! Type different keyword.',
                    'message_text'=>"Type any of the following keywords\n
calendar
recent
firstclass
ANY
ODI
Tests
Twenty20
T20
player [player name] {ex: player sachin}"
                ]],'',60);
            break;

        }
    }

?>