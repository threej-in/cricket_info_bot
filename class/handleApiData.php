<?php
//get your api key from cricapi.com website
define('CRICAPI', '');


function get_data_from_db($identifier){
    global $cricdb, $jarvis;

    $sql = "SELECT * FROM TGDATA_TABLE_3J WHERE UID = ?";
    $arr = [
        [&$identifier, 's']
    ];
    if($cricdb->prepare($sql, $arr) === -1){
        $jarvis->e(-1, '');
    }
    if($cricdb->num_rows > 0){
        return $cricdb->fetch();
    }
    return 0;    
}
function insert_data($identifier, $data){
    global $cricdb, $jarvis;
    
    $date = time();
    $sql = "INSERT IGNORE INTO TGDATA_TABLE_3J(UID, DATA, DATE) VALUES(?,?,?)";
    $arr = [
        [&$identifier, 's'],
        [&$data, 's'],
        [&$date, 'i']
    ];
    if($cricdb->prepare($sql, $arr) === -1){
        $jarvis->e(-1,'');
    }
    return $cricdb->affected_rows;
}
function update_data($identifier, $data){
    global $cricdb, $jarvis;
    
    $date = time();
    $sql = "UPDATE TGDATA_TABLE_3J SET DATA=?, DATE=? WHERE UID = ?";
    $arr = [
        [&$data, 's'],
        [&$date, 'i'],
        [&$identifier, 's']
    ];
    if($cricdb->prepare($sql, $arr) === -1){
        $jarvis->e(-1,'');
    }
    return $cricdb->affected_rows;
}
//fetch data from api
function calendar($queryId){
    global $jarvis,$threej, $cricdb;
    $url = 'https://cricapi.com/api/matchCalendar?apikey='.CRICAPI;
    $data = get_data_from_db('calendar');
    if(isset($data['DATE'])){
        $datatime = $data['DATE'];
        $current = time();
        if($current-$datatime > 12*3600){
            $data = file_get_contents($url);
            update_data('calendar', $data);
            $data = $threej->json__decode($data, 1);
        }else{
            $data = json_decode($data['DATA'], true);
        }
    }elseif($data === 0){
        $data = file_get_contents($url);
        insert_data('calendar', $data);
        $data = $threej->json__decode($data, 1);
    }else{
        return $jarvis->e(-1, $cricdb->error);
    }
    $arr = [];
    $i=1;
    if(!isset($data['data'])){ return $jarvis->e(-1, $data);}
    foreach($data['data'] as $k => $match){
        $arr[] = [
            'type'=>'article',
            'id'=>$i,
            'title'=>"$i: ".$match['name']."]\n".$match['date'],
            'message_text'=>"<b>\xF0\x9F\x8F\x8F ".$match['name']."\n\xF0\x9F\x93\x86 ".$match['date']."</b>",
            'parse_mode'=>'HTML'
        ];
        $i++;
        if($i === 49){
            break;
        }
    }
    $re = $threej->json__decode($jarvis->answerInlineQuery($queryId, $arr, '', 12*3600),1);
    if($re['ok'] !== 1){
        return $jarvis->e(-1, $re);
    }
}
//search player using api
function search_player($playerName, $queryId){
    global $jarvis, $cricdb;
    $process = 1;
    $url = 'https://cricapi.com/api/playerFinder?apikey='.CRICAPI.'&name='.rawurlencode($playerName);
    $playerName = preg_replace('/ /','%',$playerName);
    $playerName = "p-%$playerName%";
    $sql = "SELECT * FROM TGDATA_TABLE_3J WHERE UID LIKE ?";
    $arr = [
        [&$playerName, 's']
    ];
    if($cricdb->prepare($sql, $arr) === -1){return $jarvis->e(-1, '');}
    try{
        $i=0;
        $players=[];
        $players2=[];
        $pid=[];
        $current = time();
        //fetch data from db
        do{
            $result = $cricdb->fetch();
            if(isset($result['DATA'])){
                $process =0;
                $playerData = json_decode($result['DATA'], true);
                $players[$i] = $playerData;
                if($current-$result['DATE'] > 7*24*3600){
                    $pid[] = $playerData['id'];
                }
            }
            if($i>25){break;}
            $i++;
        }while($result !== 0);        
        //fetch data from api
        if($process === 1){
            $result = file_get_contents($url);
            $result = json_decode($result, true);
            $i=0;
            foreach($result['data'] as $pdata){
                $purl = 'https://cricapi.com/api/playerStats?apikey='.CRICAPI.'&pid='.$pdata['pid'];
                
                $player = file_get_contents($purl);
                $players2[$i]= new_player(json_decode($player, true));
                if($players2[$i] === -1){continue;}

                if(update_data('p-'.$pdata['name'], json_encode($players2[$i])) === 0){
                    insert_data('p-'.$pdata['name'], json_encode($players2[$i]));
                }
                if($i>25){break;}
                $i++;
            }
            $players = array_merge($players, $players2);
        }
    }catch(Exception $e){
        return $jarvis->e(-1,$e->getMessage()." \r\nOn Line: ".$e->getLine()." \r\nIn File: ".$e->getFile()."\r\n\r\n");
    }
    $jarvis->answerInlineQuery($queryId, $players,'',3600*12);
    update_players_data($pid);
}
function update_players_data($playerid){
    foreach($playerid as $k => $pid){
        $purl = 'https://cricapi.com/api/playerStats?apikey='.CRICAPI.'&pid='.$pid;
        
        $apiData = file_get_contents($purl);
        $apiData = json_decode($apiData,true);
        $playerData = new_player($apiData, true);
        if(update_data('p-'.$apiData['name'], json_encode($playerData)) === 0){
            insert_data('p-'.$apiData['name'], json_encode($playerData));
        }
    }
}
function new_player($data){
    if(!isset($data['data'])){return -1;}
    $batting="<code><b> TOUR   | MAT | RUNS  | SIX | FOUR | 50  | 100</b>\n";
    foreach($data['data']['batting'] as $tournament => $value){
        $batting .= sprintf("\n %-6s | %-3s | %-5s | %-3s | %-4s | %-3s | %-3s \n",
                substr($tournament,0,6),
                $value['Mat'] ?? 0,
                $value['Runs']??0,
                $value['6s'] ?? 0,
                $value['4s'] ?? 0,
                $value['50']??0,
                $value['100']??0);
    }
    $batting .= "</code>";
    $bowling="<code><b> TOUR   | MAT | RUNS  | BALLS | WKTS | ECON </b>\n";
    foreach($data['data']['bowling'] as $tournament => $value){
        $bowling .= sprintf("\n %-6s | %-3s | %-5s | %-5s | %-4s | %-3s \n",
                substr($tournament,0,6),
                $value['Mat'] ?? 0,
                $value['Runs']??0,
                $value['Balls'] ?? 0,
                $value['Wkts'] ?? 0,
                $value['Econ']??0);
    }
    $bowling .= "</code>";
    $name = strtoupper($data['fullName']);
    $articleUrl = urlencode($data['name']);
    $cur = new DateTime(date('M d Y',time()));
    $birth = preg_split('/, [A-Z|a-z]/', $data['born'])[0];
    if(!strtotime($birth)){}else{
        $birth = new DateTime($birth);
        $age = $cur->diff($birth);
        $data['currentAge'] = $age->y.'years';
    }
    
    $text = "<b>$name</b>

<code>Birth date:</code> <b>{$data['born']}</b>
<code>Age       :</code> <b>{$data['currentAge']}</b>
<code>Played for:</code> <b>{$data['country']}</b>
<code>As        :</code> <b>{$data['playingRole']}

- - - - - - - - BATTING CAREER - - - - - - - - </b>
$batting

<b>- - - - - - - BOWLING CAREER - - - - - - -</b>
$bowling

https://threej.in/cricket/player/$articleUrl";
    if($data['imageURL'] == 'null' || $data['imageURL'] == 'NULL' || $data['imageURL'] == null){
        $data['imageURL']= 'https://threej.in/contents/icon/threej.jpg';
    }
    $dataArr = [
        'type'=>'article',
        'id'=>strval($data['pid']),
        'url'=>'https://threej.in/cricket/player/'.urlencode($data['name']),
        'hide_url'=>false,
        'title'=>$data['fullName'],
        'description'=>$data['profile'],
        'message_text'=>$text,
        'disable_web_page_preview'=>false,
        'parse_mode'=>'HTML',
        'thumb_url'=> $data['imageURL']
        ];
    return $dataArr;
}
function new_upcoming_match_data($data){
    $arr = [];
    $i[1]=1;$i[2]=1;$i[3]=1;$i[4]=1;$i[5]=1;$i[6]=1;$i[7]=1;
    foreach($data['matches'] as $k => $match){
        if($match['matchStarted'] !== 1){
            if($match['type'] === 'Twenty20'){
                if(isset($arr['Twenty20']) && count($arr['Twenty20'])>49) continue;
                $str = $match['dateTimeGMT'];
                $mid = strpos($str, 'T');
                $date = substr($str, 0, $mid);
                $time = rtrim(substr($str,$mid+1),'Z');
                $arr['Twenty20'][] = [
                    'type'=>'article',
                    'id'=>$i[1],
                    'title'=>"{$i[1]}: ".$match['team-1'].' VS '.$match['team-2']."  ".$date,
                    'message_text'=>"<b>\xF0\x9F\x8F\x8F ".$match['team-1']." VS ".$match['team-2']."\n\xF0\x9F\x93\x86 ".$date."\n\xF0\x9F\x95\x94 ".$time." [GMT]</b>",
                    'parse_mode'=>'HTML'
                ];
                $i[1]++;
                
            }elseif($match['type'] === 'ODI'){
                if(isset($arr['ODI']) && count($arr['ODI'])>49) continue;
                $str = $match['dateTimeGMT'];
                $mid = strpos($str, 'T');
                $date = substr($str, 0, $mid);
                $time = rtrim(substr($str,$mid+1),'Z');
                $arr['ODI'][] = [
                    'type'=>'article',
                    'id'=>$i[2],
                    'title'=>"{$i[2]}: ".$match['team-1'].' VS '.$match['team-2']." ".$date,
                    'message_text'=>"<b>\xF0\x9F\x8F\x8F ".$match['team-1']." VS ".$match['team-2']."\n\xF0\x9F\x93\x86 ".$date."\n\xF0\x9F\x95\x94 ".$time." [GMT]</b>",
                    'parse_mode'=>'HTML'
                ];
                $i[2]++;
            }elseif($match['type'] === 'Tests' || $match['type'] === 'tests'){
                if(isset($arr['Tests']) && count($arr['Tests'])>49) continue;
                $str = $match['dateTimeGMT'];
                $mid = strpos($str, 'T');
                $date = substr($str, 0, $mid);
                $time = rtrim(substr($str,$mid+1),'Z');
                $arr['Tests'][] = [
                    'type'=>'article',
                    'id'=>$i[3],
                    'title'=>"{$i[3]}: ".$match['team-1'].' VS '.$match['team-2']." ".$date,
                    'message_text'=>"<b>\xF0\x9F\x8F\x8F ".$match['team-1']." VS ".$match['team-2']."\n\xF0\x9F\x93\x86 ".$date."\n\xF0\x9F\x95\x94 ".$time." [GMT]</b>",
                    'parse_mode'=>'HTML'
                ];
                $i[3]++;
                
            }elseif($match['type'] === 'First-class'){
                if(isset($arr['First-class']) && count($arr['First-class'])>49) continue;
                $str = $match['dateTimeGMT'];
                $mid = strpos($str, 'T');
                $date = substr($str, 0, $mid);
                $time = rtrim(substr($str,$mid+1),'Z');
                $arr['First-class'][] = [
                    'type'=>'article',
                    'id'=>$i[4],
                    'title'=>"{$i[4]}: ".$match['team-1'].' VS '.$match['team-2']." ".$date,
                    'message_text'=>"<b>\xF0\x9F\x8F\x8F ".$match['team-1']." VS ".$match['team-2']."\n\xF0\x9F\x93\x86 ".$date."\n\xF0\x9F\x95\x94 ".$time." [GMT]</b>",
                    'parse_mode'=>'HTML'
                ];
                $i[4]++;
            }elseif($match['type'] === 'listA'){
                if(isset($arr['listA']) && count($arr['listA'])>49) continue;
                $str = $match['dateTimeGMT'];
                $mid = strpos($str, 'T');
                $date = substr($str, 0, $mid);
                $time = rtrim(substr($str,$mid+1),'Z');
                $arr['listA'][] = [
                    'type'=>'article',
                    'id'=>$i[5],
                    'title'=>"{$i[5]}: ".$match['team-1'].' VS '.$match['team-2']." ".$date,
                    'message_text'=>"<b>\xF0\x9F\x8F\x8F ".$match['team-1']." VS ".$match['team-2']."\n\xF0\x9F\x93\x86 ".$date."\n\xF0\x9F\x95\x94 ".$time." [GMT]</b>",
                    'parse_mode'=>'HTML'
                ];
                $i[5]++;
            }else{
                if(isset($arr['ANY']) && count($arr['ANY'])>49) continue;
                $str = $match['dateTimeGMT'];
                $mid = strpos($str, 'T');
                $date = substr($str, 0, $mid);
                $time = rtrim(substr($str,$mid+1),'Z');
                $arr['ANY'][] = [
                    'type'=>'article',
                    'id'=>$i[6],
                    'title'=>"{$i[6]}: ".$match['team-1'].' VS '.$match['team-2']." ".$date,
                    'message_text'=>"<b>\xF0\x9F\x8F\x8F ".$match['team-1']." VS ".$match['team-2']."\n\xF0\x9F\x93\x86 ".$date."\n\xF0\x9F\x95\x94 ".$time." [GMT]</b>",
                    'parse_mode'=>'HTML'
                ];
                $i[6]++;
            }
        }
    }
    return $arr;
}
function upcoming_match($queryId, $type){
    
    global $jarvis, $threej, $cricdb;
    $url = 'https://cricapi.com/api/matches?apikey='.CRICAPI;
    $data = get_data_from_db($type);
    if(isset($data['DATE'])){
        $datatime = $data['DATE'];
        $current = time();
        if($current-$datatime > 12*3600){
            $data = file_get_contents($url);
            $arr = new_upcoming_match_data($threej->json__decode($data,1));
            foreach($arr as $type => $value){
                if(update_data($type, json_encode($value))=== -1){
                    insert_data($type, json_encode($value));        
                }
            }
            
            $dataArr = $arr[$type];
        }else{
            $dataArr = json_decode($data['DATA'], true);
        }
    }elseif($data === 0){
        $data = file_get_contents($url);
        $arr = new_upcoming_match_data($threej->json__decode($data,1));
        foreach($arr as $type => $value){
            if(update_data($type, json_encode($value)) === -1){
                insert_data($type, json_encode($value));
            }
        }
        $dataArr = $arr[$type];
    }else{
        return $jarvis->e(-1, $cricdb->error);
    }
    
    $jarvis->answerInlineQuery($queryId, $dataArr,'', 12*3600);
}

function new_score($url){
    global $jarvis, $threej;
    $url = 'https://www.cricapi.com'.$url;
    $data = file_get_contents($url);
    
    error_reporting(1);
    $html = new DOMDocument;
    $html->loadHTML($data);
    $table = $html->getElementsByTagName('table');
    if($table->item(0) === NULL){return -1;}
    $rows = $table->item(0)->getElementsByTagName('tr');
    $i=0;$index=0;
    $resultData=[];

    $score=0;
    foreach($rows as $row){
        
        if($row->getElementsByTagName('th')->length === 3)
            $trchild = 'th';
        else
            $trchild = 'td';
        $j=0;
        foreach($row->getElementsByTagName($trchild) as $td){
        
            if(!isset($td->nodeValue) || empty($td->nodeValue)){
                $j++;
                continue;
            }
            $textdata = preg_replace('/\s+/',' ', $td->nodeValue);
            if($trchild === 'th'){
                if($i>1){
                    $j==0 ? $resultData[$i][$j] = '-':'';
                    $j==1 ? $resultData[$i][$j] = 'Total':'';                
                    if($j==2){
                        $resultData[$i][$j] = $score.' + extras';
                        $score=0;
                    }

                    $j==2 ? $resultData[$i+1][$j] = '<b>Runs</b>' :
                    $resultData[$i+1][$j] ="<b>".$textdata.'</b>';    
                    
                }else{
                    $j==2 ? $resultData[$i][$j] = '<b>Runs</b>' :
                    $resultData[$i][$j] ="<b>".$textdata.'</b>';
                }
                
                
            }else{
                $j==0? $resultData[$i][$j]=$index.'. '.$textdata:
                $resultData[$i][$j] =$textdata;
                if($j == 2){$score += $textdata;}
            }
            $j++;
        }
        
        if($i>49) break;
        if($trchild === 'th' && $i>1){$i++;$index=0;}
        $i++;$index++;
    }
    if($i<2){return-1;}
    $resultData[$i][1] = 'Total';
    $resultData[$i][2] = strval($score)." + extras";
    $i=0;
    $arr = [];$player='<code>';
    foreach($resultData as $data){
        $arr[$i] = [
            'player'=>$data[0],
            'status'=>$data[1],
            'runs'=>$data[2],
        ];    
        $player .=sprintf("%-30s | %-40s | %-10s\n", $arr[$i]['player'],$arr[$i]['status'],$arr[$i]['runs']);
        $i++;
    }
    return $player.'</code>';
}
function recent_match($queryId){
    global $jarvis;
    $process =0;
    $dataArr=[];
    //$url = 'https://cricapi.com/api/cricket?apikey='.CRICAPI;
    $recent = get_data_from_db('recent');
    
    if(isset($recent['DATA'])){
        $current = time();
        if($current - $recent['DATE'] > 3600){
            $process =1;
        }else{
            $process = 0;
        }
        $dataArr = json_decode(($recent['DATA']),true);
    }else{
        $process =1;
    }
    if($process === 1){
        $jarvis->answerInlineQuery($queryId, $dataArr);
        /*$data = file_get_contents($url);
        $data = json_decode($data,true);*/
        $data = file_get_contents('https://www.cricapi.com/matches/');
        error_reporting(1);
        $html = new DOMDocument();
        $html->loadHTML($data);

        $a = $html->getElementsByTagName('a');
        $arr=[];$j=1;
        for($i = 24; $i<100; $i++){
            $url = $a->item($i)->attributes->item(0)->value;
            
            if(preg_match('/fantasy/',$url)){continue;}
            /*foreach($data['data'] as $match => $detail){
            $str = $detail['title'];
            $str = preg_replace('# v #',' vs ',$str);
            $str = preg_replace('#[\d|/| * ]#',' ',$str);
            $str = trim($str);
            $str = preg_replace('/\s+/','-',$str);
            $message_text = new_score($detail['unique_id'], $str);*/
            $detail = $a->item($i)->parentNode->parentNode->nodeValue;
            $detail = preg_split('/\n/',preg_replace('/\n\s+\n/',"\n",$detail));
            $title = trim($detail[1] ?? '');
            preg_match('/Fantasy/',$detail[2]??"")?$score='':$score = trim($detail[2]??'');
            $message_text = new_score($url);
            if($message_text===-1) continue;
            $message_text .="
Result: ".str_replace('/ v /', ' ', $score)."
source: <code>https://cricapi.com</code>

provided by: @cricket_info_bot";
            
            $arr[] = [
                'type'=>'article',
                'id'=>$i,
                'title'=>"$j: ".$title,
                'message_text'=>$message_text,
                'parse_mode'=>'HTML'
            ];
            if($j>12){break;}
            $j++;
        }
        if(isset($recent['DATA'])){
            update_data('recent', json_encode($arr));
        }else{
            insert_data('recent', json_encode($arr));
        }
    }else{
        $jarvis->answerInlineQuery($queryId, $dataArr,'',3600);
    }
}
