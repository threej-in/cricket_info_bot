
<?php 
//Start of live_matches function

function live_matches($url){
  if(!is_string($url)){
    return "url must be a string";
  }
    $i=0;
    $inline_keyboard = [];
    $html = file_get_html($url);
    foreach($html->find('h3.cb-lv-scr-mtch-hdr') as $e){
      if(isset($e->innertext)){
        $linktosend = "";
        foreach($e->find('a') as $link){
          $linktosend .= $link->href;
        }
        $inline_keyboard[$i][0] = 
          [
              'text' =>$e->plaintext,
              'callback_data' => $linktosend
          ];
      }
      $i++;
      if($i>9){break;}
    }
    $ik = json_encode($inline_keyboard);
    return $ik;
}
//End of live_matches function

function im_content($url, $type){
  $html = file_get_html($url);
  $i = 0;
  foreach($html->find('div.cb-lv-main h2') as $e){
    if(!empty($e->plaintext))
      {
        $inline_keyboard[$i][0] = ['text'=>$e->plaintext, 'callback_data'=>$type.$i];
        $i++;
      }
  }
  $ik = json_encode($inline_keyboard);
  return $ik;
}

//Start of Upcoming_matches function
function upcoming_matches($data){
  $re = substr($data,0, strpos($data,'textis')-1);
  $qtxt = substr($data, strpos($data,'textis')+6);  
    $i=0; $z=-1;
    $text="<b>\nLIST OF UPCOMING MATCHES IN ".$qtxt."</b>\n\n";
    $html = file_get_html("https://www.cricbuzz.com/cricket-match/live-scores/upcoming-matches");
    foreach($html->find('div.cb-lv-main') as $x){
      $z++;
      if($z != $re){
        continue;
      }
      foreach($x->find('div.cb-mtch-lst') as $e){
        foreach($e->find('h3.cb-lv-scr-mtch-hdr') as $f){
          if(isset($f->plaintext)){
            $text .= "<b>[".($i+1)."]".$f->plaintext."</b>";
        }
        }
        foreach($e->find('span.schedule-date') as $g){
          $time = date("d M @ h:m a", floor(($g->timestamp)/1000));
          $text .= "\nDate: ".$time;
        }
        foreach($e->find('div.text-gray') as $f){
          if(isset($f->plaintext)){
            $text .= "\nVenue: <i>".$f->plaintext."</i>\n\n";
        }
        }
        $text = preg_replace('/&nbsp;|&#8226;| at |  /', '', $text);
        $i++;
        if($i>6){break;}
      }
    }
      $text .= "<i>Source</i>: <a href=\"https://cricbuzz.com\">Cricbuzz</a>";
      return $text;
}
//End of upcoming matches function

//Start of Recent matches function
function recent_matches($data){
  $re = substr($data,0, strpos($data,'textis')-1);
  $qtxt = substr($data, strpos($data,'textis')+6);  
  $i=0; $z=-1;
  $text = "<b>LIST OF RECENT MATCHES IN ".$qtxt."</b>\n\n";
  $html = file_get_html("https://www.cricbuzz.com/cricket-match/live-scores/recent-matches");
  foreach($html->find('div.cb-lv-main') as $x){
    $z++;
    if($z != $re){
      continue;
    }
    foreach($x->find('div.cb-tms-itm') as $e){
      foreach($e->find('h3.cb-lv-scr-mtch-hdr') as $f){
        $text .= "<b>[".($i+1)."]".$f->plaintext."</b>\n";
        
        foreach($e->find('h3.cb-lv-scr-mtch-hdr a') as $g){
          $link = $g->href;
          $inline_keyboard[$i][0] =[
            'text'=> "Get scorecard of [".($i+1)."]",
            'callback_data'=> substr($link, 20)
          ];
        }
        $i++;
      }
      foreach($e->find('span.schedule-date') as $g){
        $time = date("d M Y @ h:m a", floor(($g->timestamp)/1000));
        $text .= "<i>Match Date: ".$time."\n</i>";
      }
      foreach($e->find('div.cb-scr-wll-chvrn') as $f){
        if(isset($f->plaintext)){
          $text .= "<b>".$f->plaintext."</b>\n";
      }}
      if($i >4){break;}
    }
  }
    $text = str_replace(')', ")\n", $text);
    $text .= "<i>Source</i>: <a href=\"https://cricbuzz.com\">Cricbuzz</a>";
    $inline_keyboard = json_encode($inline_keyboard);
    return $text." inline_keyboardis ".$inline_keyboard;
}
//End of recent matches function

//Start of mini score function
function mini_score($url){
  $text = "<b>MiniScore</b>";
  $temp = "";
  $data = $url;
  $url = "https://cricbuzz.com".$url;
  $html = file_get_html($url);
  foreach($html->find('div.cb-col-scores') as $e){
    $temp =  $e->plaintext;
  }
  if(empty($temp)){$text .= "Match not yet started";}
  $text .= preg_replace('/  /',"\n",preg_replace('/&nbsp;/', " ", $temp));
  $text .= "<i>Source</i> : <a href=\"https://cricbuzz.com\">Cricbuzz</a>";
  foreach($html->find('nav.cb-nav-bar a') as $e){
    $temp =  $e->plaintext."<br>";
    if(preg_match('/scorecard/i', $temp)){
      $scorecard = $e->href;
      $scorecard = substr($scorecard,23);
    }elseif(preg_match('/points table/i', $temp)){
      $pointtable = $e->href;
    }
  }
  $inline_keyboard[0][0] = 
  [
      'text' => "REFRESH",
      'callback_data' => $data
  ];
  $inline_keyboard[1][0] = 
  [
      'text' => "Scorecard",
      'callback_data' => $scorecard
  ];
  $inline_keyboard[2][0] = 
  [
      'text' => "Points Table",
      'callback_data' => $pointtable
  ];
  return $text." inline_keyboardis ".json_encode($inline_keyboard);
}
//End of mini score function


//Start of Scorecard function
function scorecard($url){
  if(!is_string($url)){
    return false;
  }
  $url = "https://www.cricbuzz.com/live-cricket-scorecard".$url;
  $datatosend ="";
  $html = file_get_html($url);
  $datatosend.=  $html->find('div.cb-scrcrd-status', 0)->plaintext;
  foreach($html->find('div.cb-scrd-lft-col div.cb-ltst-wgt-hdr') as $e){
    if(isset($e->plaintext) && !preg_match('/(powerplays|fall)/i', $e->plaintext)){
      foreach($e->find('div.cb-scrd-hdr-rw') as $f){
        $datatosend.=  "\n\n".$f->plaintext."\n";
      }
      foreach($e->find('div.cb-scrd-sub-hdr') as $f){
        $datatosend.=  "\n<b>".$f->plaintext."</b>";
      }
      foreach($e->find('div.cb-scrd-itms') as $f){
        $datatosend.= "\n";
        foreach($f->find('div') as $g){
          $datatosend.=  $g->plaintext." : ";
      }
    }
    $datatosend.= "\n\n";
  }
  }
  $datatosend = substr($datatosend, 0, 3906);
  $datatosend .="\n\nNote: Here R for run, B for Balls, SR for Strike rate, (c) for captain, (wk) for wicket keeper, O for overs, NB for No balls,\n\n <i>source</i>: <a href=\"www.cricbuzz.com\">cricbuzz</a>";
  return $datatosend;
}
//END of Scorecard function

function points_table($url){
  if(!is_string($url)){
    return false;
  }
  $url = "https://cricbuzz.com".$url;
  $html = file_get_html($url);
  $data = "<b>POINTS TABLE</b>\n\n<b>Teams	Mat	Won	Lost	Tied	NR	Pts	NRR</b>\n";
  foreach($html->find('div.cb-left table tbody tr') as $e){
    foreach($e->find('td.cb-srs-pnts-name') as $f){
      $data .= "\n".$f->plaintext;
    }
    foreach($e->find('td.cb-srs-pnts-td') as $f){
      $data .= " : ".$f->plaintext;  
    }
  }
  $data .= "\n\n<i>source</i>: <a href=\"www.cricbuzz.com\">cricbuzz</a>";
  return $data;
}
