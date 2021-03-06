<?php
require_once('./Phirehose/lib/Phirehose.php');
require_once('./Phirehose/lib/UserstreamPhirehose.php');

/**
 * Barebones example of using UserstreamPhirehose.
 */
class MyUserConsumer extends UserstreamPhirehose 
{
  /**
   * First response looks like this:
   *    $data=array('friends'=>array(123,2334,9876));
   *
   * Each tweet of your friends looks like:
   *   [id] => 1011234124121
   *   [text] =>  (the tweet)
   *   [user] => array( the user who tweeted )
   *   [entities] => array ( urls, etc. )
   *
   * Every 30 seconds we get the keep-alive message, where $status is empty.
   *
   * When the user adds a friend we get one of these:
   *    [event] => follow
   *    [source] => Array(   my user   )
   *    [created_at] => Tue May 24 13:02:25 +0000 2011
   *    [target] => Array  (the user now being followed)
   *
   * @param string $status
   */
  public function enqueueStatus($status)
  {
    /*
     * In this simple example, we will just display to STDOUT rather than enqueue.
     * NOTE: You should NOT be processing tweets at this point in a real application, instead they
     *  should be being enqueued and processed asyncronously from the collection process. 
     */
    $data = json_decode($status, true);
    echo date("Y-m-d H:i:s (").strlen($status)."):".print_r($data,true)."\n";
  }

}

// The OAuth credentials you received when registering your app at Twitter
define("TWITTER_CONSUMER_KEY", "Wb308y70OgdfHyOhDudAuA");
define("TWITTER_CONSUMER_SECRET", "xvCr9ryx8sNk9tkaPcoNDSseBRRCpjHg2YlBELcuo");


// The OAuth data for the twitter account
define("OAUTH_TOKEN", "17577573-DBtgQCbegA6r3hPAG38R6bOeEfDXSnu5mjHnVQN1E");
define("OAUTH_SECRET", "CZTEzMgVQu1O63Cw6s1ntQopFf9UBJ2vgnVDjcUAds");

// Start streaming
$sc = new MyUserConsumer(OAUTH_TOKEN, OAUTH_SECRET);
$sc->consume();
