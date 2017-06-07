<?php 

Class SimpleCurl{
    
    private $curl;
    private $proxy;
    private $useragent;
    private $port;
    private $host;
    private $debug_mode;
    private $debug_postfix;
    
    public function __construct($host, $useragent = '', $proxy = '', $port = '', $debug_mode = false){
        $this->curl = curl_init();
        $this->host = $host;
        $this->useragent = $useragent;
        $this->proxy = $proxy;
        $this->port = $port;
        $this->debug_mode = $debug_mode;
        $this->debug_postfix = 0;
    }

    private function setReferer($referer){
        //Wenn es einen Referer gibt wird, dieser gesetzt
        if(!empty($referer)){
            curl_setopt($this->curl, CURLOPT_REFERER, $referer); 
        }
    }

    private function setUseragent($useragent){
        //Wenn kein Nutzeragent übergeben wird, setzen wir standardmäßig einen Linux Firefox Useragent
        if(!empty($useragent)){
            curl_setopt($this->curl, CURLOPT_USERAGENT, $useragent); 
        }else{
            curl_setopt($this->curl, CURLOPT_USERAGENT, "Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:33.0) Gecko/20100101 Firefox/33.0"); 
        }
    }
	
    private function getStandardOptions(){
        //Setzen der Standartoptionen
        return array(
            CURLOPT_AUTOREFERER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_COOKIEFILE => "cookies",
            CURLOPT_COOKIEJAR => "cookies",
        );
    }

    public function request($url= '', $type='GET', $referer= '', $data = array()){
        $options = $this->getStandardOptions();
        $options[CURLOPT_URL] = 'http://'.$this->host.'/'.$url;
        //Überprüfen ob es ein Get oder POST Request werden soll
        switch($type){
             case 'GET':
                $options[CURLOPT_HTTPGET] = true;
                break;
            case 'POST':
                $options[CURLOPT_POST] = count($data);
                $data_string = $data;
                if(is_array($data)){
                    $data_string = http_build_query($data);				
                }
                $options[CURLOPT_POSTFIELDS] = $data_string;
                break;
            default:
                throw new SimpleCurlException('Error, unsupported HTTP Type (Only GET / POST)');
                break;
        }
		
		//Setzen von Referer und Useragent
        $this->setReferer($referer);
        $this->setUseragent($this->useragent);

        curl_setopt_array($this->curl, $options);
        $html = curl_exec($this->curl);
		
        if($this->debug_mode){
            $this->debug($html);
        }

        if (curl_error($this->curl)) {
                echo curl_error($this->curl);
                throw new SimpleCurlException('Error, Error during Request');
        }
        return $html;
    }
	
	private function debug($html){
            //Speichern der Rückgabe in eine HTML Datei, der debug_postifx wird automatisch um eins erhöht
            if(!is_dir('cache')){
                mkdir('cache');
            }
            file_put_contents('cache/debug'.$this->debug_postfix++.'.html', $html);
	}
}

Class SimpleCurlException extends Exception{
    
}