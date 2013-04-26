<?php

require_once '../Libraries/Config.php';
require_once '../Libraries/phirehose/lib/Phirehose.php';
require_once '../Libraries/phirehose/lib/OauthPhirehose.php';
require_once '../Libraries/db/db_lib.php';



class TrackTwitterAccounts extends OauthPhirehose {
    
    
    const QUEUE_FILE_PREFIX = 'stream';
    const QUEUE_FILE_ACTIVE = 'stream.current';
    
    protected $db;
    protected $queueDir;
    protected $rotateInterval;
    protected $streamFile;
    protected $statusStream;
    protected $lastRotated;
    
    public function __construct($username, $password, $queueDir = './streams', $rotateInterval = 60) {
        parent::__construct($username, $password, Phirehose::METHOD_FILTER, Phirehose::FORMAT_JSON);
        $this->db = new db(Config::DB_HOST, Config::DB_USER, Config::DB_PASSWORD, Config::DB_NAME);  
        $userIds = $this->getAccounts();
        $this->setFollow($userIds);
        
        if ($rotateInterval < 5) {
            throw new Exception('Rotate interval set too low - Must be >= 5 seconds');
        }
        
        $this->queueDir = $queueDir;
        $this->rotateInterval = $rotateInterval;
    }

    /**
     * Enqueue each status
     *
     * @param string $status
     */
    public function enqueueStatus($status) {
        // Write the status to the stream (must be via getStream())
        fputs($this->getStream(), $status);

        /* Are we due for a file rotate? Note this won't be called if there are no statuses coming through - 
         * This is (probably) a good thing as it means the collector won't needlessly rotate empty files. That said, if
         * you have a very sparse/quiet stream that you need highly regular analytics on, this may not work for you. 
         */
        $now = time();
        if (($now - $this->lastRotated) > $this->rotateInterval) {
            // Mark last rotation time as now
            $this->lastRotated = $now;

            // Rotate it
            $this->rotateStreamFile();
        }
    }
    
    /**
     * Returns a stream resource for the current file being written/enqueued to
     * 
     * @return resource
     */
    private function getStream() {
        // If we have a valid stream, return it
        if (is_resource($this->statusStream))
            return $this->statusStream;
        

        // If it's not a valid resource, we need to create one
        if (!is_dir($this->queueDir) || !is_writable($this->queueDir)) {
          throw new Exception('Unable to write to queueDir: ' . $this->queueDir);
        }

        // Construct stream file name, log and open
        $this->streamFile = $this->queueDir . '/' . self::QUEUE_FILE_ACTIVE;
        $this->log('Opening new active status stream: ' . $this->streamFile);
        $this->statusStream = fopen($this->streamFile, 'a'); // Append if present (crash recovery)

        // Ok?
        if (!is_resource($this->statusStream)) {
          throw new Exception('Unable to open stream file for writing: ' . $this->streamFile);
        }

        // If we don't have a last rotated time, it's effectively now
        if ($this->lastRotated == NULL) {
          $this->lastRotated = time();
        }

        // Looking good, return the resource
        return $this->statusStream;
    }
    
    /**
    * Rotates the stream file if due
    */
    private function rotateStreamFile() {
        // Close the stream
        fclose($this->statusStream);

        // Create queue file with timestamp so they're both unique and naturally ordered
        $queueFile = $this->queueDir . '/' . self::QUEUE_FILE_PREFIX . '.' . date('Ymd-His') . '.queue';

        // Do the rotate
        rename($this->streamFile, $queueFile);

        // Did it work?
        if (!file_exists($queueFile)) 
          throw new Exception('Failed to rotate queue file to: ' . $queueFile);

        // At this point, all looking good - the next call to getStream() will create a new active file
        $this->log('Successfully rotated active stream to queue file: ' . $queueFile);
    }   
 
    private function getAccounts() {
        $query = 'SELECT idStr FROM Politico';
        $results = $this->db->select($query);
        $userIds = array();
        while($row = mysqli_fetch_assoc($results)) {
            $userIds[] = $row['idStr'];
        }
        return $userIds;
    }

    public function checkFilterPredicates() {
        $userIds = $this->getAccounts();
        $this->setFollow($userIds);
    }
}

// Start streaming
$sc = new TrackTwitterAccounts(Config::USER_TOKEN, Config::USER_SECRET);
$sc->consumerKey = Config::CONSUMER_KEY;
$sc->consumerSecret = Config::CONSUMER_SECRET;
$sc->consume();

