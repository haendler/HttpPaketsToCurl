<?php

$message = '';

function getReferer($paket){
    foreach($paket as $paketLine){
        if(strpos($paketLine, 'Referer:') !== false){
            return trim(str_replace('Referer: ', '', $paketLine));
        }
    }
}

function getContentLength($paket){
    foreach($paket as $paketLine){
        if(strpos($paketLine, 'Content-Length: ') !== false){
            return triim(str_replace('Content-Length: ', '', $paketLine));
        }
    }
}
function getHost($paket){
    foreach($paket as $paketLine){
        if(strpos($paketLine, 'Host: ') !== false){
            return trim(str_replace('Host: ', '', $paketLine));
        }
    }
}

function getLink($paket){
    foreach($paket as $paketLine){
        if(strpos($paketLine, 'GET ') !== false || strpos($paketLine, 'POST') !== false){
            return trim(str_replace(array('GET ', 'POST ', 'HTTP/1.1'), array('','',''), $paketLine));
        }
    }
}

function isGet($paket){
    foreach($paket as $paketLine){
        if(strpos($paketLine, 'GET ') !== false){
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
    foreach($paket as $paketLine){
        if(strpos($paketLine, 'Content-Length: ') !== false){
            $contentLength = str_replace('Content-Length: ', '', $paketLine);
            break;
        }
    }
    
    if(!$contentLength){
        return '';
    }
    
    foreach($paket as $paketLine){
        if(strlen($paketLine)-1 == $contentLength){
            return trim($paketLine);
        }
    }
    return '';
}
if(isset($_POST['submit'])){
    if(isset($_FILES['file']['tmp_name'])){
        $phpFunctions = '';
        $phpFunctions .= 'function _inConsole($s){'."\n";
	    $phpFunctions .= "\t".'echo "[".date("H")."][".date("i")."][".date("s")."]: ".$s."\n";'."\n";
        $phpFunctions .= '}';
        $phpFunctions .= "\n\n";
        $phpFunctions .= 'function _StringBetween($content,$start,$end){'."\n";
        $phpFunctions .= "\t".'$r = explode($start, $content);'."\n";
        $phpFunctions .= "\t".'if (isset($r[1])){'."\n";
        $phpFunctions .= "\t"."\t".'$r = explode($end, $r[1]);'."\n";
        $phpFunctions .= "\t"."\t".'return $r[0];'."\n";
        $phpFunctions .= "\t".'}'."\n";
        $phpFunctions .= "\t".'return "";'."\n";
        $phpFunctions .= '}'."\n";
        
        $phpCode = '<?php '."\n\n";
        $phpCode .= 'require("lib/SimpleCurl.php")'."\n";
        $phpCode .= '$debugMode = true;'."\n";
        $phpCode .= "\n";
        
        $phpBotCode = '';
        $phpBotScript = '';
        
        $skippedPakets = 0;
        $crawledPakets = 0;
        
        $aHosts = array();
        
        $httpPaketsAsString = file_get_contents($_FILES['file']['tmp_name']);
        
        //Mitschnitt in einzelne Pakete trennen
        $httpPakets = explode('----------------------------------------------------------', $httpPaketsAsString);
        foreach($httpPakets as $paket){
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
                $skippedPakets++;
                continue;
            }
            
            $sLink = getLink($aPaketLines);
            $sHost = getHost($aPaketLines);
            
            if(!in_array($sHost, $aHosts)){
                $aHosts[] = $sHost;
                $phpBotCode = '$Bot'.(count($aHosts)-1);
                $phpBotScript .= "\n";
                $phpBotScript .= $phpBotCode.' = new SimpleCurl("'.$sHost.'", "Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:36.0) Gecko/20100101 Firefox/36.0", "", "", $debugMode);'."\n";
            }else{
                $phpBotCode = '$Bot'.array_search($sHost, $aHosts);
            }
            
            $sReferer = getReferer($aPaketLines);
            if(isGet($aPaketLines)){
                if(isSSL($aPaketLines)){
                    $phpBotScript .= '$sHTML = '.$phpBotCode.'->sslrequest("'.$sLink.'", "GET", "'.$sReferer.'");'."\n";
                }else{
                    $phpBotScript .= '$sHTML = '.$phpBotCode.'->request("'.$sLink.'", "GET", "'.$sReferer.'");'."\n";
                }
            }else{
                $sPostData = getPostData($aPaketLines);
                if(isSSL($aPaketLines)){
                    $phpBotScript .= '$sHTML = '.$phpBotCode.'->sslrequest("'.$sLink.'", "POST", "'.$sReferer.'", "'.$sPostData.'");'."\n";
                }else{
                    $phpBotScript .= '$sHTML = '.$phpBotCode.'->request("'.$sLink.'", "POST", "'.$sReferer.'", "'.$sPostData.'");'."\n";
                }
            }
            $crawledPakets++;
        }
        
        $phpCode .= $phpFunctions .= $phpBotScript;
        //file_put_contents('Bot.php', $phpCode);

        header('content-type: text/plain');
        header('Content-Disposition: attachment; filename="Bot.php"');
        echo $phpCode;
        exit;

        $message = 'Der Bot wurde erstellt. Es wurden '.$crawledPakets.' Pakete bearbeitet und '.$skippedPakets.' uebersprungen';
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