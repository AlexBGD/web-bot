<?php

/**
 * Description of Web_bot
 *
 * @author aleksa
 * 
 * contact <qwertalex@yahoo.com>
 */
class Web_bot {
    
    
    const CURL_TIMEOUT=120;
    const USER_AGENG="web_bot";
    
    protected $url;






public function http($url) {
      
ob_start();         
$ch=  curl_init($url);
 $options=array(
       CURLOPT_RETURNTRANSFER=>TRUE,
       CURLOPT_USERAGENT=>self::USER_AGENG,
       CURLOPT_TIMEOUT=>self::CURL_TIMEOUT,
       CURLOPT_REFERER=>"",
       CURLOPT_FOLLOWLOCATION=>TRUE,
       CURLOPT_MAXREDIRS=>4,
);
        
 curl_setopt_array($ch, $options);    
        
 if(curl_exec($ch) === false){throw new Exception("Error: ".curl_error($ch));}
    $return_array['page']=curl_exec($ch);
    $return_array['info']=  curl_getinfo($ch);
 
curl_close($ch);

ob_clean();

return $return_array;
  }
    
    
    
    
    
    
   /**
    * 
    * @param string $url
    * @return boolean
    * 
    * 
    * 
    * *********************************************************************************
    * NE SKIDA SLIKE UBACENE PREKO CSS-a!                                             *
    * AKO SLIKE SA ISTIM NAZIVOM VEC POSTOJE AUTOMATSKI IH ZAMENJUJE!                 *
    * *********************************************************************************
    */ 
  
  public function download_images($url) {
      $curl=  $this->http($url);
     if (substr(trim($url), -1)!=="/") {
         $url=$url."/";
                
            }
            
     $page=  $this->tidy_repair_html($curl['page']);

     preg_match_all("/(<img.*>)/siU",$page,$match);
     
     if (count($match[0])==0) {return FALSE;}
    $file="/var/www/img_dir";
if (!file_exists($file)) {mkdir($file);}

     $allowed_extensions=array("jpg","jpeg","gif","png");
     
foreach ($match[0] as $v) {
   
    
    preg_match("/src=[\"\'](.*)[\"\']/siU", $v,$src);
    
    if (!in_array(pathinfo($src[1], PATHINFO_EXTENSION), $allowed_extensions)) {continue;}
    
    
    if (strpos($src[1], "http://")!==FALSE&&$this->broken_link($src[1])) {
          $this->download_binary_file($src[1], $file);
    }
    elseif ( strpos($src[1], "http://")===FALSE) {
        if (substr(trim($src[1]), 0)==="/") { $src[1]=substr($src[1], 1);}
       
if ($this->broken_link($url.$src[1])) {
       
    $this->download_binary_file($url.$src[1], $file);
}
        
    }
  
}
 
  }
  
  
  
  
  /**
   * 
   * @param type $url
   * @param type $file_local
   * @throws Exception
   * 
   * 
   */
  
  public function download_binary_file($url,$file_local) {
      
     
      
      $ch=  curl_init($url);
        
   $options=array(
       CURLOPT_BINARYTRANSFER=>TRUE,  
       CURLOPT_RETURNTRANSFER=>TRUE,
       CURLOPT_USERAGENT=>self::USER_AGENG,
       CURLOPT_TIMEOUT=>self::CURL_TIMEOUT,
       CURLOPT_REFERER=>"",
       CURLOPT_FOLLOWLOCATION=>TRUE,
       CURLOPT_MAXREDIRS=>4,//prati najvise 4 redirekcije
      
       
       );
        
 curl_setopt_array($ch, $options);  
      
     if( curl_exec($ch) === false){throw new Exception("Error: ".curl_error($ch));}
     
     
    $curl=curl_exec($ch);
     curl_close($ch);
  
     if (!file_exists($file_local)) {mkdir($file_local, 0644);  }
     
     $fopen=  fopen($file_local."/".  basename($url), "w");
     fputs($fopen, $curl);
     fclose($fopen);
     
   }





  /**
   * 
   * @param type $html
   * @return type
   * 
   * 
   * 
   * 
   */
  private function tidy_repair_html($html) {
      if (function_exists('tidy_parse_string')) {
          $tidy=new tidy();
          $html=$tidy->repairString($html,array(/*'uppercase-attributes' => true,*/"wrap"=>0));
         }
     return $html;
  }



/**
 * 
 * @param type $string
 * @param type $start
 * @param type $end
 * @return type
 * 
 * 
 */
  private function string_between($string,$start,$end) {
       preg_match_all("/({$start}(.*){$end})/siU",$string,$match);
       return $match;
  }


/**
 * 
 * @param type $string
 * @param type $attribute
 * @return boolean
 * 
 */
  private function attribute_value($string,$attribute) {
        if (strpos($string, $attribute)===FALSE) { return FALSE; }
        $mat=$this->string_between($string, $attribute."=[\"\']", '[\"\']');
 return $mat;
      
      
  }



/**
 * 
 * @param type $url
 * @return boolean
 * 
 * 
 * *****************************************************************************************************
 * OPTIS:                                                                                              *
 * OVA FUNKCIJA VRACA LINKOVE SA CELE STRANE, NE SA CELOG SAITA!                                       *
 * NE RADI AKO LINKOVI IDU KA PARENT DIRECTORY NPR:                                                    *
 *<code>a href="../../../directory/page.php"></code>                                                   *
 * OVO (#) JE TAKODJE BROKEN LINK KAO I PRAZAN href tag (" ");                                         *
 * *****************************************************************************************************
 * 
 * <code>
 * 
$web_bot=new Web_bot();
$web_bot->parse_links('http://www.trance.fm/');
 * 
 * 
 * </code>
 * 
 * ******************************************************************************
 * RETURN:
 * TYPE: ARRAY;
 * <code>
$web_bot=new Web_bot();
$array=$web_bot->parse_links('http://www.trance.fm/');
***************
#losi linkovi;*
$array['bad'];*
***************
 * 
 * *************
#dobri linkovi:*
$array['good'];*
****************
 * 
 * *****************
#external links:   *
$array['external'];*
********************
 * 
 * *****************
#good ssl links    *
$array['good_ssl'];*
********************
 * 
 * ****************************
#mailto (mail se ne validira):*
$array['mailto'];             *
 * ****************************
 *                                                                                      
 * </code>                                                                     
 *                                                                              *
 * ******************************************************************************
 * 
 * 
 */
  public function parse_links($url) {
      if (substr(trim($url), -1)!=="/") {$url=$url."/";}  
preg_match('/^(?:http|https):\/\/www.(.*)\//', $url,$dom);
$domain=$dom[1];

      $curl=  $this->http($url);
      $page=  $this->tidy_repair_html($curl['page']);
      $match=$this->string_between($page, "<a", ">");
     if (count($match[0])==0) {return FALSE;}
     
     
  $urls=array();
  
  
  
foreach ($match[0] as $v) {
   $mat_dva=$this->attribute_value($v, "href");
   $string=trim($mat_dva[2][0]);
 
   #ako link pocinje sa "/" onda brises prvi karakter!
   if (substr(trim($string), 0,1)==="/") {$string=  substr($string, 1);}

   

    if (in_array($string, $urls)) {continue;}


 if (strpos($string, "mailto:")!==FALSE) {$links['mailto'][]=$string;continue;}  
 
 #if (!preg_match("/^[a-z]/i",$string)) { $links['bad'][]=$string;  continue;}
 
 if (strpos($string, "http://")===FALSE&&preg_match("/^[a-z]/i",$string)&&strpos($string, "https://")===FALSE) { $string=$url.$string;} 


if (strpos($string, "http://")!==FALSE||strpos($string, "https://")!==FALSE) {
    if (strpos($string, "#")!==FALSE) {
        $string=  substr($string, 0,  strpos($string, "#"));
    }
    if (in_array($string, $urls)) {continue;}
    $urls[]=$string;

}


}  //END FOREACH LOOP


#pripremiti array za multiple curl
foreach ($urls as $k=> $v) {
   
           $URLS[$k]=array(
        "url"=>$v,
        "option"=>FALSE,
        "default_options"=>FALSE
        
    );
        
    }
 

    $multi_http=$this->multi_http($URLS);
    foreach ($multi_http as $key => $value) {
          switch ($value['http_code']) {
         case 200:
         case 301:
         case 302:
               if (strpos($value['url'], $domain)===FALSE) { $links['external'][]=$value['url'];}
       else $links['good'][]=$value['url'];break;
         default:$links['bad'][]=$string; break;
         }
  }
       
return $links;
      
  }


  
  
  
  
    /**
      * 
      * @staticvar array $return_array
      * @staticvar array $parse_all_good_links
      * @param type $url
      * 
      * 
      * 
      */    

  public function parse_links_whole_site($url) {
  $parse_links=$this->parse_links($url);
  static $all_links=array();
  static $check_links=array();
  static $count=0;
  
  $GOOD=FALSE;
  $BAD=FALSE;
  $EXTERNAL=FALSE;
  if (array_key_exists("good", $parse_links)) {$GOOD=$parse_links['good'];}
  if (array_key_exists("bad", $parse_links)) {$BAD=$parse_links['bad'];}
  if (array_key_exists("external", $parse_links)) {$EXTERNAL=$parse_links['external'];}
  
  
  $all_links[$url]=array(
    "good"=>$GOOD,
    "bad"=>$BAD,
    "external"=>$EXTERNAL
  );
  

  foreach ($all_links[$url]['good'] as $link) {
      
       if (!array_key_exists($link, $all_links)) {
         $check_links[]=$link;
           
       } 
  }
  
  var_dump($check_links);
 }




  /**
   * 
   * @param array $arr
   * @return type
   * 
   * 
   * 
   * 
   */
  public function multi_http(array $arr) {

foreach ($arr as $k => $v) {
    $ch[$k]=  curl_init();
    
     foreach ($v as $key => $value) {
            if ($key==="default_options"&&$value===FALSE) {
                   $options=array(
       CURLOPT_URL=> $v['url'],
       CURLOPT_RETURNTRANSFER=>1,
       CURLOPT_USERAGENT=>self::USER_AGENG,
       CURLOPT_TIMEOUT=>self::CURL_TIMEOUT,
       CURLOPT_REFERER=>"",
       CURLOPT_FOLLOWLOCATION=>TRUE,
       CURLOPT_MAXREDIRS=>4,//prati najvise 4 redirekcije
        );
       
       curl_setopt_array($ch[$k], $options);
    }
    
    
    /********************************************
     * NIJE DOVOLJNO TESTIRANO!!!!!!!!          * 
     ********************************************/
    if ($key==="option"&&$value===FALSE) { if(isset($options)) curl_setopt_array($ch[$k], $options);}
     elseif($key==="option"&&is_array($value)){
         foreach ($v as $key => $value) {$options[$key]=$value;}
          curl_setopt_array($ch[$k], $options);
      }   
     /********************************************
      * NIJE DOVOLJNO TESTIRANO!!!!!!!!          * 
      ********************************************/       
    }
    
   }
      
      
      
 #multi session!    
$mh = curl_multi_init();


#Adds the $ch handle to the multi handle $mh
foreach ($ch as $val) {curl_multi_add_handle($mh,$val);}

$running=null;
//execute the handles
do {
  curl_multi_exec($mh,$running);

} while ($running > 0);


#Remove the $ch handle to the multi handle $mh
foreach ($ch as $val) {curl_multi_remove_handle($mh, $val);}


#add info to $ch
foreach ($ch as $val) {  $info[]=  curl_getinfo($val);}

#close sessions
curl_multi_close($mh);

return $info;

   
  }






  /**
  * 
  * @param string $url
  * @return boolean | if link is broken return false, if link is good return true;
  * 
  * 
  * var_dump(Broken_links::broken_link('http://php.net'));
  * 
  * OR
  * 
new Broken_links('http://php.net');
  */   
 public function broken_link($url='') {
         #  if (!$url&&isset(self::$url)) {$url=  self::$url;}
        if (!filter_var($url, FILTER_VALIDATE_URL)) {throw new Exception('********************self::$url is not defined**********************************');}  
      $curl=  $this->http($url);
      $info=$curl['info'];
    switch ($info['http_code']) {
         case 200:
         case 301:
         case 302:
         return TRUE;break;
         default:return FALSE; break;
         }
     
 }
  
  
  
}


