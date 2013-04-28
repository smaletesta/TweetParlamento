<?php

require_once '../Libraries/Config.php';
require_once '../Libraries/db/db_lib.php';

class Tweet {
    
    const QUEUE_FILE_PREFIX = 'stream';
    const QUEUE_FILE_ACTIVE = 'stream.current';
    
    protected $db;
    protected $queueDir;
    protected $filePattern;
    protected $checkInterval;


    public function __construct($queueDir = './streams', $checkInterval = 10) {
        $this->db = new db(Config::DB_HOST, Config::DB_USER, Config::DB_PASSWORD, Config::DB_NAME);
        $this->queueDir = $queueDir;
        $this->filePattern = 'stream*.queue';
        $this->checkInterval = $checkInterval;
        /*$today = new DateTime('now');
        $this->filePattern = 'stream.'.$today->format('Ymd').'-*.queue';*/

    }

    public function process() {

        // Init some things
        $lastCheck = 0;

        // Loop infinitely
        while (TRUE) {

            // Get a list of queue files
            $queueFiles = glob($this->queueDir . '/' . $this->filePattern);
            $accounts = $this->getAccounts();
            $lastCheck = time();

            $this->log('Found ' . count($queueFiles) . ' queue files to process...');

            // Iterate over each file (if any)
            foreach ($queueFiles as $queueFile) {
                $this->processQueueFile($queueFile, $accounts);
            }

            // Wait until ready for next check
            $this->log('Sleeping...');
            while (time() - $lastCheck < $this->checkInterval) {
                sleep(1);
            }
        } // Infinite loop

    } // End process()
    
    private function getAccounts() {
        $query = 'SELECT idStr FROM Politico';
        $results = $this->db->select($query);
        $userIds = array();
        while($row = mysqli_fetch_assoc($results)) {
            $userIds[] = $row['idStr'];
        }
        return $userIds;
    }
    
    private function processQueueFile($queueFile, $accounts) {
        
        $this->log('Processing file: ' . $queueFile);
        // Open file
        $fp = fopen($queueFile, 'r');
        
        // Check if something has gone wrong, or perhaps the file is just locked by another process
        if (!is_resource($fp)) {
            $this->log('WARN: Unable to open file or file already open: ' . $queueFile . ' - Skipping.');
            return FALSE;
        }
    
        // Lock file
        flock($fp, LOCK_EX);
        
        // Loop over each line (1 line per status)
        while ($rawStatus = fgets($fp, 8192)) {
            $data = json_decode($rawStatus, true);
            if (is_array($data) && isset($data['user']['screen_name'])) {

                // Controllo se il tweet lo ha creato un politico
                if (in_array($data['user']['id_str'],  $accounts, TRUE)) {
                    // Tweet normale
                    if (!array_key_exists('retweeted_status', $data)) {
                        $cloud = $this->wordFrequency($data['text']);
                        if($cloud){
                            $this->insertTweet(
                                array('NULL',
                                    '"'.$this->db->date($data['created_at']).'"',
                                    '"'.$this->db->escape($data['id_str']).'"',
                                    '"'.$this->db->escape($data['text']).'"',
                                    $this->createdByPolitico($data['user']['id_str']),
                                    '"'.$this->db->escape(serialize($cloud)).'"',
                                    0,
                                    0
                                ));
                        }
                    } else { //retweet ad un altro parlamentare
                        if (in_array($data['retweeted_status']['user']['id_str'],  $accounts, TRUE))
                            $this->updateTweet($data['retweeted_status']['id_str'], "numRetweet = numRetweet + 1");   
                    }
                    //risposta ad un altro parlamentare
                    if ($data['in_reply_to_user_id_str'] && in_array($data['in_reply_to_user_id_str'],  $accounts, TRUE)) {
                        $this->updateTweet($data['in_reply_to_status_id_str'], "numReplies = numReplies + 1");
                    }
                } else {
                    //risposta ad un parlamentare
                    if ($data['in_reply_to_user_id_str'] && in_array($data['in_reply_to_user_id_str'],  $accounts, TRUE)) {
                        $this->updateTweet($data['in_reply_to_status_id_str'], "numReplies = numReplies + 1");
                    }
                    //retweet ad un parlamentare
                    if (array_key_exists('retweeted_status', $data)) {
                        if (in_array($data['retweeted_status']['user']['id_str'],  $accounts, TRUE))
                                $this->updateTweet($data['retweeted_status']['id_str'], "numRetweet = numRetweet + 1");
                    }
                }
            }
      
        } // End while
    
        // Release lock and close
        flock($fp, LOCK_UN);
        fclose($fp);
        
        // All done with this file
        $this->log('Successfully processed tweets from ' . $queueFile . ' - deleting.');
        unlink($queueFile);    
    }

    private function createdByPolitico($user_id) {
        $query = 'SELECT id FROM Politico WHERE idStr = '.$user_id;
        $results = $this->db->select($query);
        if(!$results)
            return null;
        $row = mysqli_fetch_assoc($results);
        return $row['id'];
    }
    
    private function insertTweet($values) {
        $this->db->insert('Tweet', $values);
    }

    private function updateTweet($idStr, $values) {
        $this->db->update('Tweet', $values, "idStr = '".$this->db->escape($idStr)."'");
    }

    private function log($message,$level='notice') {
        @error_log('Save tweets: ' . $message, 0);
    }

    private function wordFrequency($tweet) {
        $frequencyList = array();
        $words = explode(" ", $this->preprocessTweet($tweet));
        if(count($words) == 1 && $words[0] == '')
            return NULL;
        foreach($words as $word){
            if (array_key_exists($word, $frequencyList)) 
                ++$frequencyList[$word];
            else
                $frequencyList[$word] = 1;
        }
        arsort($frequencyList);
        return $frequencyList;
    }
    
    private function preprocessTweet($tweet) {

        $stopWords = array('ad','al','allo','ai','agli','all','agl','alla',
            'alle','con','col','coi','da','dal','dallo','dai','dagli','dall',
            'dagl','dalla','dalle','di','del','dello','dei','degli','dell',
            'degl','della','delle','in','nel','nello','nei','negli','nell',
            'negl','nella','nelle','su','sul','sullo','sui','sugli','sull',
            'sugl','sulla','sulle','per','tra','contro','io','tu','lui','lei',
            'noi','voi','loro','mio','mia','miei','mie','tuo','tua','tuoi',
            'tue','suo','sua','suoi','sue','nostro','nostra','nostri','nostre',
            'vostro','vostra','vostri','vostre','mi','ti','ci','vi','lo','la',
            'li','le','gli','ne','il','un','uno','una','ma','ed','se',
            'perché','anche','come','dov','dove','che','chi','cui','non',
            'no','più','quale','quanto','quanti','quanta','quante','quello',
            'quelli','quella','quelle','questo','questi','questa','queste',
            'si','tutto','tutti','a','c','e','é','i','l','o','ho','hai','ha',
            'abbiamo','avete','hanno','abbia','abbiate','abbiano','avrò',
            'avrai','avrà','avremo','avrete','avranno','avrei','avresti',
            'avrebbe','avremmo','avreste','avrebbero','avevo','avevi',
            'aveva','avevamo','avevate','avevano','ebbi','avesti','ebbe',
            'avemmo','aveste','ebbero','avessi','avesse','avessimo','avessero',
            'avendo','avuto','avuta','avuti','avute','','','sono','sei','è',
            'siamo','siete','sia','siate','siano','sarò','sarai','sarà',
            'saremo','sarete','saranno','sarei','saresti','sarebbe',
            'saremmo','sareste','sarebbero','ero','eri','era','eravamo',
            'eravate','erano','fui','fosti','fu','fummo','foste','furono',
            'fossi','fosse','fossimo','fossero','essendo','faccio',
            'fai','facciamo','fanno','faccia','facciate','facciano','farò',
            'farai','farà','faremo','farete','faranno','farei','faresti',
            'farebbe','faremmo','fareste','farebbero','facevo','facevi',
            'faceva','facevamo','facevate','facevano','feci','facesti','fece',
            'facemmo','faceste','fecero','facessi','facesse','facessimo',
            'facessero','facendo','','','sto','stai','sta','stiamo','stanno',
            'stia','stiate','stiano','starò','starai','starà','staremo',
            'starete','staranno','starei','staresti','starebbe','staremmo',
            'stareste','starebbero','stavo','stavi','stava','stavamo','via',
            'stavate','stavano','stetti','stesti','stette','stemmo','steste',
            'stettero','stessi','stesse','stessimo','stessero','stando','rt',
            '0','1','2','3','4','5','6','7','8','9','q','w','e','r','t','y',
            'u','i','o','p','a','s','d','f','g','h','j','k','l','z','x','c',
            'v','b','n','m',0,1,2,3,4,5,6,7,8,9
            );
        $tweet = mb_strtolower($tweet, 'UTF8');
        foreach($stopWords as $stopWord){
            //$tweet = preg_replace("/\b".$stopWord."\b/i", "", $tweet);
            $tweet = preg_replace("/(?<![\pL\pN])".$stopWord."(?![\pL\pN])/u", "", $tweet);  
        }
        $tweet = preg_replace("%(http|https)://[^\s]+%u", '', $tweet);
        //strip screenname
        $tweet = preg_replace("%@[^\s]+%u", '', $tweet);
        $tweet = strip_tags($tweet);
        //punteggiatura
        $tweet = preg_replace('/[^\p{L}0-9\s]|\n|\r/u','',$tweet);
        //spazi extra
        $tweet = preg_replace('/ +/',' ',$tweet);
      
        $tweet = trim($tweet);
        return $tweet;
    }
 
    //da usare solo una volta per aggiungere la wordcloud ai tweet esistenti
    public function addCloud() {
        $query = 'SELECT testo,id from Tweet';
        $results = $this->db->select($query);
        if(!$results)
            return null;
        while($row = mysqli_fetch_assoc($results)) {
            $cloud = $this->wordFrequency($row['testo']);
            if($cloud){
                $values = "wordCloud = '".serialize($cloud)."'";
                $this->db->update('Tweet', $values, "id = '".$row['id']."'");
            }
        }
    }
    
}

?>
