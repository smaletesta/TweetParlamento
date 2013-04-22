<?php

require_once '../Libraries/Config.php';
require_once '../Libraries/tmhOAuth/tmhUtilities.php';
require_once '../Libraries/tmhOAuth/tmhOAuth.php';
require_once '../Libraries/db/db_lib.php';


class Politico {
    
    private $tmhOAuth;
    private $db;
    private $maxRetry = 5;
    
    public function __construct() {
        $this->tmhOAuth = new tmhOAuth(array(
            'consumer_key'    => Config::CONSUMER_KEY,
            'consumer_secret' => Config::CONSUMER_SECRET,
            'user_token'      => Config::USER_TOKEN,
            'user_secret'     => Config::USER_SECRET,
        ));
        $this->db = new db(Config::DB_HOST, Config::DB_USER, Config::DB_PASSWORD, Config::DB_NAME);
    }
    
    private function twitterAccountInfo($parametro, $tipo) {
        $options = array();
        if($tipo == 'screenname')
            $options['screen_name'] = $parametro;
        else
            $options['user_id'] = $parametro;
        $success = false;
        $retry = 0;
        while(!$success && $retry < $this->maxRetry){
            $retry++;
            $httpResponseCode = $this->tmhOAuth->request('GET', $this->tmhOAuth->url('1.1/users/show.json'), $options);
            if ($httpResponseCode == 200 || $httpResponseCode == 429){
                $twitterResponse = $this->tmhOAuth->response;
                $success = $this->checkResponse($twitterResponse);
            } else {
                echo "HTTP Response Code ".$httpResponseCode." per l'account ".$parametro.".\n";
                sleep(30);
            }
        }
        if($success)
            return $this->tmhOAuth->response['response'];
        
        return null;
    }
    
    private function getPolitico() {
        $query = 'SELECT id, idStr FROM Politico';
        $results = $this->db->select($query);
        return $results;
    }
    
    private function updateDb($response, $id) {
        $today = new DateTime('midnight');
        $decodedResponse = json_decode($response);
        $values = "screenname = '".$this->db->escape($decodedResponse->screen_name)."', bio = '".$this->db->escape($decodedResponse->description)."', profileImage ='".$this->db->escape($decodedResponse->profile_image_url)."', numFollower ='".$decodedResponse->followers_count."'";
        $this->db->update('Politico', $values, 'id = '.$id);
        $accountValues = array('NULL',
            "'".$today->format('Y-m-d')."'",
            $decodedResponse->followers_count,
            $decodedResponse->friends_count,
            $decodedResponse->statuses_count,
            $id);
        $this->db->insert('Account', $accountValues);
        
    }
    
    private function updatePartitoDb($nome, $cognome, $partito) {
        $values = "gruppo = '".$this->db->escape($partito)."'";
        $this->db->update('Politico', $values, "nome = '".$this->db->escape($nome)."' AND cognome = '".$this->db->escape($cognome)."'");    
    }
    private function findIdPolitico($nome, $cognome) {
        $select ="SELECT id FROM Politico WHERE nome = '".$nome."' AND cognome = '".$cognome."'";
        $result = $this->db->select($select);
        return $result;
    }

    private function addPolitico($cognome, $nome, $ramo, $gruppo, $circoscrizione, $screenName) {
        $accountInfo = $this->twitterAccountInfo($screenName, 'screenname');
        if($accountInfo) {
            $data = json_decode($accountInfo);
            $values = array('NULL',
                "'".$this->db->escape($nome)."'",
                "'".$this->db->escape($cognome)."'",
                "'".$this->db->escape($ramo)."'",
                "'".$this->db->escape($gruppo)."'",
                "'".$this->db->escape($circoscrizione)."'",
                "'".$this->db->escape($data->screen_name)."'",
                $data->id_str,
                "'".$this->db->escape($data->profile_image_url)."'",
                "'".$this->db->escape($data->description)."'",
                $data->followers_count);
            $this->db->insert('Politico', $values);
            $today = new DateTime('midnight');
            $politici = $this->findIdPolitico($nome, $cognome);
            $id = NULL;
            while($politico = mysqli_fetch_assoc($politici)) {
                $id = $politico['id'];
            }
            $accountValues = array('NULL',
                "'".$today->format('Y-m-d')."'",
                $data->followers_count,
                $data->friends_count,
                $data->statuses_count,
                $id);
            $this->db->insert('Account', $accountValues);
        } else
            echo 'Impossibile inserire nel db '.$nome.' '.$cognome.'. Controllare lo screenname '.$screenName."\n";
    }
    
    public function update() {
        $politici = $this->getPolitico();
        while($politico = mysqli_fetch_assoc($politici)) {
            $data = $this->twitterAccountInfo($politico['idStr'], 'idStr');
            if($data)
                $this->updateDb($data, $politico['id']);
            else
                echo 'Update del record '.$politico['id'].' fallito'."\n";
        }
        
    }
    
    public function addFromCsv($file, $ramo) {
        $fp = fopen($file, 'r');
        while($row = fgetcsv($fp)) {
            $this->addPolitico($row[0], $row[1], $ramo, $row[2], $row[3], $row[4]);
        }
        fclose($fp);
    }
    
    public function updateFromCsv($file) {
        $fp = fopen($file, 'r');
        while($row = fgetcsv($fp)) {
            $this->updatePartitoDb($row[1], $row[0], $row[2]);
        }
        fclose($fp);
    }
    
    private function checkResponse($twitterResponse) {
        if (!$twitterResponse){
            echo "Errore, sleep per 900 sec.\n";
            sleep(900);
            return false;
        }
        //perchè a volte twitter ritorna X-Rate-Limit-Remaining e a volte x-rate-limit-remaining
        $headers = array_change_key_case ($twitterResponse['headers'], CASE_LOWER);

        //Controllo se è presente il parametro, anche se twitter risponde con code=200|429
        if (!array_key_exists('x-rate-limit-remaining', $headers)){
            echo "Errore, sleep per 900 sec.\n";
            sleep(900);
            return false;
        }
        if(is_array($headers['x-rate-limit-remaining'])){
            if ($headers['x-rate-limit-remaining'][count($headers['x-rate-limit-remaining']) - 1] == '0'){
                if(is_array($headers['x-rate-limit-reset'])) {
                    if($headers['x-rate-limit-reset'][count($headers['x-rate-limit-reset']) - 1] > 0) {
                        echo "Rate limit raggiunto, sleep per ".($headers['x-rate-limit-reset'][count($headers['x-rate-limit-reset']) - 1] - time())." sec.\n";
                        sleep($headers['x-rate-limit-reset'][count($headers['x-rate-limit-reset']) - 1] - time());
                    } else {
                        echo "Rate limit raggiunto, ma sleeptime minore di zero, sleep per 30 sec.";
                        sleep(30);
                    }
                    return false;
                } else {
                    if($headers['x-rate-limit-reset'] - time() > 0){
                        echo "Rate limit raggiunto, sleep per ".($headers['x-rate-limit-reset'] - time())." sec.\n";
                        sleep($headers['x-rate-limit-reset'] - time());
                    } else {
                        echo "Rate limit raggiunto, ma sleeptime minore di zero, sleep per 30 sec.";
                        sleep(30);
                    }
                    return false;
                }
            }
        } else {
            if ($headers['x-rate-limit-remaining'] == '0'){
                if(is_array($headers['x-rate-limit-reset'])) {
                    if($headers['x-rate-limit-reset'][count($headers['x-rate-limit-reset']) - 1] > 0) {
                        echo "Rate limit raggiunto, sleep per ".($headers['x-rate-limit-reset'][count($headers['x-rate-limit-reset']) - 1] - time())." sec.\n";
                        sleep((int)($headers['x-rate-limit-reset'][count($headers['x-rate-limit-reset']) - 1]) - time());
                    } else {
                        echo "Rate limit raggiunto, ma sleeptime minore di zero, sleep per 30 sec.";
                        sleep(30);
                    }
                    return false;
                } else {
                    if($headers['x-rate-limit-reset'] - time() > 0){
                        echo "Rate limit raggiunto, sleep per ".($headers['x-rate-limit-reset'] - time())." sec.\n";
                        sleep((int)$headers['x-rate-limit-reset'] - time());
                    } else {
                        echo "Rate limit raggiunto, ma sleeptime minore di zero, sleep per 30 sec.";
                        sleep(30);
                    }
                    return false;
                }
            }
        }
        
        return true;
    }
    
    //da usare sono per sistemare i Cognomi
    public function capitalizeCognomi() {
        $query = 'SELECT cognome,id from Politico';
        $results = $this->db->select($query);
        if(!$results)
            return null;
        while($row = mysqli_fetch_assoc($results)) {
            $values = "cognome = '".ucfirst($this->db->escape(mb_strtolower($row['cognome'], 'UTF8')))."'";
            $this->db->update('Politico', $values, "id = '".$row['id']."'");
        }
    }
    
}
 
?>
