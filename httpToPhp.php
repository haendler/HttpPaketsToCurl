<?php

$message = '';

function getReferer($paket){
    foreach($paket as $paket_zeile){
        if(strpos($paket_zeile, 'Referer:') !== false){
            return trim(str_replace('Referer: ', '', $paket_zeile));
        }
    }
}

function getContentLength($paket){
    foreach($paket as $paket_zeile){
        if(strpos($paket_zeile, 'Content-Length: ') !== false){
            return triim(str_replace('Content-Length: ', '', $paket_zeile));
        }
    }
}
function getHost($paket){
    foreach($paket as $paket_zeile){
        if(strpos($paket_zeile, 'Host: ') !== false){
            return trim(str_replace('Host: ', '', $paket_zeile));
        }
    }
}

function getLink($paket){
    foreach($paket as $paket_zeile){
        if(strpos($paket_zeile, 'GET ') !== false || strpos($paket_zeile, 'POST') !== false){
            return trim(str_replace(array('GET ', 'POST ', 'HTTP/1.1'), array('','',''), $paket_zeile));
        }
    }
}

function isGet($paket){
    foreach($paket as $paket_zeile){
        if(strpos($paket_zeile, 'GET ') !== false){
            return true;
        }
    }
    return false;
}
function isSSL($paket){
    $urlIndex = 0;
    if(count($paket[0]) == 1){
        $urlIndex = 1;
    }
    if(strpos($paket[$urlIndex], 'https://') !== false){
        return true;
    }
    
    return false;
}

function getPostData($paket){
    $contentLength = 0;
    foreach($paket as $paket_zeile){
        if(strpos($paket_zeile, 'Content-Length: ') !== false){
            $contentLength = str_replace('Content-Length: ', '', $paket_zeile);
            break;
        }
    }
    
    if(!$contentLength){
        return '';
    }
    
    foreach($paket as $paket_zeile){ 
        if(strlen($paket_zeile)-1 == $contentLength){
            return trim($paket_zeile);
        }
    }
    return '';
}
if(isset($_POST['submit'])){
    if(isset($_FILES['file']['tmp_name'])){
        $php_functions = '';
        $php_functions .= 'function _inConsole($s){'."\n";
	    $php_functions .= "\t".'echo "[".date("H")."][".date("i")."][".date("s")."]: ".$s."\n";'."\n";
        $php_functions .= '}';
        $php_functions .= "\n\n";
        $php_functions .= 'function _StringBetween($content,$start,$end){'."\n";
        $php_functions .= "\t".'$r = explode($start, $content);'."\n";
        $php_functions .= "\t".'if (isset($r[1])){'."\n";
        $php_functions .= "\t"."\t".'$r = explode($end, $r[1]);'."\n";
        $php_functions .= "\t"."\t".'return $r[0];'."\n";
        $php_functions .= "\t".'}'."\n";
        $php_functions .= "\t".'return "";'."\n";
        $php_functions .= '}'."\n";
        
        $php_code = '<?php '."\n\n";
        $php_code .= 'require("lib/SimpleCurl.php")'."\n";
        $php_code .= '$debugMode = true;'."\n";
        $php_code .= "\n";
        
        $php_bot_code = '';
        $php_bot_script = '';
        
        $skipped_pakets = 0;
        $crawled_pakets = 0;
        
        $aHosts = array();
        
        $http_live_string = file_get_contents($_FILES['file']['tmp_name']);
        
        //Mitschnitt in einzelne Pakete trennen
        $http_live_pakets = explode('----------------------------------------------------------', $http_live_string);
        foreach($http_live_pakets as $paket){
            $aPaketLines = explode("\n", $paket);
            if(!isset($aPaketLines[3])){
                continue;
            }
            if(
                    strpos($aPaketLines[3], '.png') !== false ||
                    strpos($aPaketLines[3], '.jpg') !== false ||
                    strpos($aPaketLines[3], '.css') !== false ||
                    strpos($aPaketLines[3], '.js') !== false ||
                    strpos($aPaketLines[3], '.xml') !== false ||
                    strpos($aPaketLines[3], '.gif') !== false ||
                    strpos($aPaketLines[3], '.swf') !== false ||
                    strpos($aPaketLines[3], '.ico') !== false ||
                    strpos($aPaketLines[3], 'safebrowsing.'
            )){
                $skipped_pakets++;
                continue;
            }
            
            $sLink = getLink($aPaketLines);
            $sHost = getHost($aPaketLines);
            
            if(!in_array($sHost, $aHosts)){
                $aHosts[] = $sHost;
                $php_bot_code = '$Bot'.(count($aHosts)-1);
                $php_bot_script .= "\n";
                $php_bot_script .= $php_bot_code.' = new SimpleCurl("'.$sHost.'", "Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:36.0) Gecko/20100101 Firefox/36.0", "", "", $debugMode);'."\n";
            }else{
                $php_bot_code = '$Bot'.array_search($sHost, $aHosts);
            }
            
            $sReferer = getReferer($aPaketLines);
            if(isGet($aPaketLines)){
                if(isSSL($aPaketLines)){
                    $php_bot_script .= '$sHTML = '.$php_bot_code.'->sslrequest("'.$sLink.'", "GET", "'.$sReferer.'");'."\n";
                }else{
                    $php_bot_script .= '$sHTML = '.$php_bot_code.'->request("'.$sLink.'", "GET", "'.$sReferer.'");'."\n";
                }
            }else{
                $sPostData = getPostData($aPaketLines);
                if(isSSL($aPaketLines)){
                    $php_bot_script .= '$sHTML = '.$php_bot_code.'->sslrequest("'.$sLink.'", "POST", "'.$sReferer.'", "'.$sPostData.'");'."\n";
                }else{
                    $php_bot_script .= '$sHTML = '.$php_bot_code.'->request("'.$sLink.'", "POST", "'.$sReferer.'", "'.$sPostData.'");'."\n";
                }
            }
            $crawled_pakets++;
        }
        
        $php_code .= $php_functions .= $php_bot_script;
        //file_put_contents('Bot.php', $php_code);

        header('content-type: text/plain');
        header('Content-Disposition: attachment; filename="Bot.php"');
        echo $php_code;
        exit;

        $message = 'Der Bot wurde erstellt. Es wurden '.$crawled_pakets.' Pakete bearbeitet und '.$skipped_pakets.' uebersprungen';
    }
}

?>

<html>
    <head><title>HttpToPhp</title></head>
    <body>
    <?php if(!empty($message)): ?>
        <p><?=$message?></p>
    <?php endif; ?>
    <form method="post" enctype="multipart/form-data">
        <input name="file" type="file" />
        <input type="submit" name="submit" />
    </form> 
    </body>
</html>