<?php

namespace Adis\IwatchyouBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Adis\IwatchyouBundle\Form\Type\SearchParlamentariType;
use DateTime;

class DefaultController extends Controller {
    
    private $maxResults = 20;
    private $maxResultsList = 5;
    private $maxResultsTimelineDettagli = 10;
    private $maxResultsTimelineFrontpage = 20;
    
    public function indexAction() {
        $em = $this->getDoctrine()->getManager();
        
        $dataInizio = new DateTime('-3 days');
        $repositoryTweet = $em->getRepository('AdisIwatchyouBundle:Tweet');
        
        $form = $this->createForm(new SearchParlamentariType());
        
        $repositoryAccount = $em->getRepository('AdisIwatchyouBundle:Account');
        $mostFollowed = $repositoryAccount->findMostFollowed($this->maxResultsList);
        $mostActive = $repositoryAccount->findMostActive($this->maxResultsList);
        $repositoryPolitico = $em->getRepository('AdisIwatchyouBundle:Politico');
        if ($this->get('request')->getMethod() == 'POST') {
            $form->bind($this->get('request'));
            if ($form->isValid()) {
                $data = $form->getData();
                $session = $this->get('session');
                $session->set('data', $data);
                $tweets = $repositoryTweet->findCloudByPoliticiFromDate($data['nome'], $data['ramo'], $data['regione'], $data['partito'], $dataInizio);
                $cloud = $this->wordFrequency($tweets);
                $paginator = $repositoryPolitico->findParlamentariPaginator($data['nome'], $data['ramo'], $data['regione'], $data['partito'], $this->maxResults, 1);
                if(count($paginator) == 0) {
                    $session->getFlashBag()->add('error', 'La ricerca non ha prodotto risultati, prova a cercare qualcos\'altro');
                    return $this->redirect($this->generateUrl('adis_iwatchyou_homepage'));
                }
                if(count($cloud) == 0)
                    $session->getFlashBag()->add('notice', 'Negli ultimi tempi i Parlamentari che hai cercato non hanno twittato, perché non li inviti a farlo utilizzando il pulsante \'Twitta\' sotto la foto?');
                $totalPages = ceil(count($paginator) / $this->maxResults);
                $end = false;
                if($totalPages == 1)
                    $end = true;
                
                
                return $this->render('AdisIwatchyouBundle:Default:index.html.twig', array('topRetweet' => NULL, 'topEngagement' => NULL, 'cloud' => $cloud, 'mostFollowed' => $mostFollowed, 'mostActive' => $mostActive, 'parlamentari' => $paginator, 'end' => $end, 'location' => 'search', 'form' => $form->createView()));
            }
        }
        $tweets = $repositoryTweet->findCloudFromDate($dataInizio);
        $cloud = $this->wordFrequency($tweets);
        $paginator = $repositoryPolitico->findAllParlamentari($this->maxResults, 1);
        $totalPages = ceil(count($paginator) / $this->maxResults);
        $topEngagement = $repositoryTweet->getTweetsByEngagement(new DateTime('today midnight'), $this->maxResultsTimelineFrontpage);
        $topEngagementLinked = array();
        foreach($topEngagement as $tweet){
            $tweet['testo'] = $this->formatUrlsInTweet($tweet['testo']);
            $topEngagementLinked[] = $tweet;
        }
        $topRetweet = $repositoryTweet->getTweetsByRetweet(new DateTime('today midnight'), $this->maxResultsTimelineFrontpage);
        $topRetweetLinked = array();
        foreach($topRetweet as $tweet){
            $tweet['testo'] = $this->formatUrlsInTweet($tweet['testo']);
            $topRetweetLinked[] = $tweet;
        }
        $end = false;
        if($totalPages == 1)
            $end = true;
        return $this->render('AdisIwatchyouBundle:Default:index.html.twig', array('topRetweet' => $topRetweetLinked, 'topEngagement' => $topEngagementLinked, 'cloud' => $cloud, 'mostFollowed' => $mostFollowed, 'mostActive' => $mostActive, 'parlamentari' => $paginator, 'end' => $end, 'location' => 'home', 'form' => $form->createView()));
    }

    private function wordFrequency($clouds) {
        $frequencyList = array();
        foreach($clouds as $cloud){
            $tempCloud = unserialize($cloud['wordCloud']);
            foreach ($tempCloud as $word => $frequency) {
                if (array_key_exists($word, $frequencyList))
                    $frequencyList[$word] += $frequency;
                else 
                    $frequencyList[$word] = $frequency;
            }
        }
        arsort($frequencyList);
        $frequencyList =  array_slice($frequencyList, 0, 20);
        $totalFrequency = 0;
        foreach ($frequencyList as $frequency){
            $totalFrequency += $frequency;
        }
        foreach ($frequencyList as $word => $frequency){
            $frequencyList[$word] = $frequency / $totalFrequency * 100;
        }
        return $frequencyList;
    }

    public function searchParlamentariAction(Request $request) {
        $session = $this->get('session');
        $data = $session->get('data');
        $page = $request->get('page');
        $em = $this->getDoctrine()->getManager();
        
        $repository = $em->getRepository('AdisIwatchyouBundle:Politico');
        $paginator = $repository->findParlamentariPaginator($data['nome'], $data['ramo'], $data['regione'], $data['partito'], $this->maxResults, $page);
        $totalPages = ceil(count($paginator) / $this->maxResults);
        $html = null;
        $i = 0;
        foreach ($paginator as $parlamentare) {
            if($i % 2 == 0)
                $html .='<div class="row-fluid">';
            $html .=    '<div class="span6">
                            <div class="well clearfix">
                                <div class="span3">
                                    <a href="parlamentare/'.$parlamentare->getId().'"><img src="'.str_replace('_normal', '_bigger', $parlamentare->getProfileImage()).'" class="img-polaroid"></img></a>
                                    <a href="http://twitter.com/intent/tweet/?text=@'.$parlamentare->getScreenName().'&hashtags=tweetparlamento" class="btn btn-primary btn-custom"><i class="icon-twitter"></i> Twitta</a>
                                </div>
                                <div class="span9">
                                    <div class="recap-parlamentare">
                                        <a href="parlamentare/'.$parlamentare->getId().'"><h3 class="name-custom">'.$parlamentare->getNome().' '.$parlamentare->getCognome().'</h3></a>
                                        <div class="more-info"><em>'.$parlamentare->getGruppo().' - '.ucfirst($parlamentare->getRamo()).'. Regione d\'elezione: '.$parlamentare->getCircoscrizione().'</em></div>
                                        <div><p>'.$parlamentare->getBio().'</p></div>
                                    </div>
                                </div>
                            </div>
                        </div>';
            if($i % 2 != 0)
                $html .= '</div>';
            $i++;
        }
        if(($i % 2 != 0) && ($i < $this->maxResults))
            $html .= '</div>';
        if($page == $totalPages)
            $html .= '<div id="end"></div>';
        return new Response($html, 200, array('Content-Type'=>'text/html'));
    }
    
    public function getParlamentariAction(Request $request) {
        $page = $request->get('page');
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('AdisIwatchyouBundle:Politico');
        $paginator = $repository->findAllParlamentari($this->maxResults, $page);
        $totalPages = ceil(count($paginator) / $this->maxResults);
       
        $html = null;
        $i = 0;
        foreach ($paginator as $parlamentare) {
            if($i % 2 == 0)
                $html .='<div class="row-fluid">';
            $html .=    '<div class="span6">
                            <div class="well clearfix">
                                <div class="span3">
                                    <a href="parlamentare/'.$parlamentare->getId().'"><img src="'.str_replace('_normal', '_bigger', $parlamentare->getProfileImage()).'" class="img-polaroid"></img></a>
                                    <a href="http://twitter.com/intent/tweet/?text=@'.$parlamentare->getScreenName().'&hashtags=tweetparlamento" class="btn btn-primary btn-custom"><i class="icon-twitter"></i> Twitta</a>
                                </div>
                                <div class="span9">
                                    <div class="recap-parlamentare">
                                        <a href="parlamentare/'.$parlamentare->getId().'"><h3 class="name-custom">'.$parlamentare->getNome().' '.$parlamentare->getCognome().'</h3></a>
                                        <div class="more-info"><em>'.$parlamentare->getGruppo().' - '.ucfirst($parlamentare->getRamo()).'. Regione d\'elezione: '.$parlamentare->getCircoscrizione().'</em></div>
                                        <div><p>'.$parlamentare->getBio().'</p></div>
                                    </div>
                                </div>
                            </div>
                        </div>';
            if($i % 2 != 0)
                $html .= '</div>';
            $i++;
        }
        if(($i % 2 != 0) && ($i < $this->maxResults))
            $html .= '</div>';
        if($page == $totalPages)
            $html .= '<div id="end"></div>';
        return new Response($html, 200, array('Content-Type'=>'text/html'));
    }
    
    public function dettagliAction($id) {
        $em = $this->getDoctrine()->getManager();
        $repositoryPolitico = $em->getRepository('AdisIwatchyouBundle:Politico');
        $politico = $repositoryPolitico->find($id);
        if(!$politico){
            $this->container->get('session')->getFlashBag()->add('error', 'Non esiste nessun parlmentare con quell\'id');
            return $this->redirect($this->generateUrl('adis_iwatchyou_homepage'));
        }
        $repositoryAccount = $em->getRepository('AdisIwatchyouBundle:Account');
        $dataInizio = new DateTime('-15 days');
        $dataFine = new DateTime('tomorrow');
        $statistiche = $repositoryAccount->getStatisticsDay($politico->getId());
        $form = $this->createForm(new SearchParlamentariType());
        $mostFollowed = $repositoryAccount->findMostFollowed($this->maxResultsList);
        $mostActive = $repositoryAccount->findMostActive($this->maxResultsList);
        
        $dataInizioCloud = new DateTime('-15 days');
        $repositoryTweet = $em->getRepository('AdisIwatchyouBundle:Tweet');
        $tweets = $repositoryTweet->findCloudByPoliticoFromDate($dataInizioCloud, $id);
        $cloud = $this->wordFrequency($tweets);
        $timeline = $repositoryTweet->getParlamentareTimeline($dataInizioCloud, $id, $this->maxResultsTimelineDettagli);
        $timelineLinked = array();
        foreach($timeline as $tweet){
            $tweet['testo'] = $this->formatUrlsInTweet($tweet['testo']);
            $timelineLinked[] = $tweet;
        }
        return $this->render('AdisIwatchyouBundle:Default:dettagli.html.twig', array('timeline' => $timelineLinked, 'cloud' => $cloud, 'statistiche' => $statistiche, 'parlamentare' => $politico, 'form' => $form->createView(), 'mostFollowed' => $mostFollowed, 'mostActive' => $mostActive, 'dataInizio' => $dataInizio->format('Y-m-d'), 'dataFine' => $dataFine->format('Y-m-d')));
    }
    
    public function getDataAction() {
        $em = $this->getDoctrine()->getManager();
        $repositoryAccount = $em->getRepository('AdisIwatchyouBundle:Account');
        $repositoryTweet = $em->getRepository('AdisIwatchyouBundle:Tweet');
        $request = $this->getRequest();
        $dataInizio = $request->get('dataInizio');
        $dataFine = $request->get('dataFine');
        $id = $request->get('id');
        $statistiche = $repositoryAccount->getStatistics(new Datetime($dataInizio), new Datetime($dataFine), $id);
        $dataFollower = array();
        $dataFollowing = array();
        $dataEngagement = array();
        $dettagliTweets = array();
        foreach ($statistiche as $statisticheGiorno) {
            $dataFollower[] = array(strtotime($statisticheGiorno->getData()->format('Y-m-d').' UTC') * 1000, $statisticheGiorno->getNumFollower());
            $dataFollowing[] = array(strtotime($statisticheGiorno->getData()->format('Y-m-d').' UTC') * 1000, $statisticheGiorno->getNumFollowing());
        }
        $tweets = $repositoryTweet->getEngagementStatistics(new Datetime($dataInizio), new Datetime($dataFine), $id);
        foreach ($tweets as $index=>$tweet) {
            $followers = NULL;
            foreach ($statistiche as $statisticheGiorno){
                //var_dump($statisticheGiorno->getData()->format('Y-m-d'));
                //var_dump($tweet->getData()->format('Y-m-d'));
                if($statisticheGiorno->getData()->format('Y-m-d') == $tweet->getData()->format('Y-m-d')){
                    $followers = $statisticheGiorno->getNumFollower();
                    
                }
            }
            if($followers){
                $dataEngagement[] = array($index, (($tweet->getNumReplies() + $tweet->getNumRetweet())/$followers)*100);
                $dettagliTweets[$index] = array('testo' => $tweet->getTesto(), 'data' => $tweet->getData()->format('H:i:s d/m/Y'));
            }
        }
        $jsonArrayFollower = array(array('label' => 'Follower', 'data' => $dataFollower));
        $jsonArrayFollowing = array(array('label' => 'Following', 'data' => $dataFollowing));
        $jsonArrayEngagement = array(array('label' => 'Engagement', 'data' => $dataEngagement));
        $datasets = array($jsonArrayFollower, $jsonArrayFollowing, $jsonArrayEngagement, $dettagliTweets);
        $response = new Response(json_encode($datasets));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }
    
    public function aboutAction(){
        $form = $this->createForm(new SearchParlamentariType());
        $em = $this->getDoctrine()->getManager();
        $repositoryAccount = $em->getRepository('AdisIwatchyouBundle:Account');
        $mostFollowed = $repositoryAccount->findMostFollowed($this->maxResultsList);
        $mostActive = $repositoryAccount->findMostActive($this->maxResultsList);
        return $this->render('AdisIwatchyouBundle:Default:about.html.twig', array('mostFollowed' => $mostFollowed, 'mostActive' => $mostActive, 'form' => $form->createView()));
    }
    
    private function formatUrlsInTweet($tweet){
        $reg_exUrl = "/(http|https)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/";
        $matches = array();
        preg_match_all($reg_exUrl, $tweet, $matches);
        $usedPatterns = array();
        foreach($matches[0] as $pattern){
            if(!array_key_exists($pattern, $usedPatterns)){
                $usedPatterns[$pattern]=true;
                $tweet = str_replace  ($pattern, '<a href="'.$pattern.'" rel="nofollow" target="blank">'.$pattern.'</a>', $tweet);   
            }
        }
        return $tweet;            
    }
    
    
    public function termineAction($word) {
        $em = $this->getDoctrine()->getManager();
        
        $dataInizio = new DateTime('-3 days'); 
        $repositoryTweet = $em->getRepository('AdisIwatchyouBundle:Tweet');
        
        $form = $this->createForm(new SearchParlamentariType());
        
        $repositoryAccount = $em->getRepository('AdisIwatchyouBundle:Account');
        $mostFollowed = $repositoryAccount->findMostFollowed($this->maxResultsList);
        $mostActive = $repositoryAccount->findMostActive($this->maxResultsList);
        
        $wordWithSpaces = ' '.$word.' ';
        
        $tweets = $repositoryTweet->findTweetsWithWordPaginator($dataInizio, $wordWithSpaces, $this->maxResults, 1);
        
        $totalPages = ceil(count($tweets) / $this->maxResults);      
        
        foreach($tweets as $tweet){
            $tweet->setTesto($this->formatUrlsInTweet($tweet->getTesto()));
        }
        
        $end = false;
        if($totalPages == 1)
            $end = true;
        
        return $this->render('AdisIwatchyouBundle:Default:termine.html.twig', array( 'mostFollowed' => $mostFollowed, 'mostActive' => $mostActive, 'tweets' => $tweets, 'end' => $end, 'location' => 'termine', 'word' => $word, 'form' => $form->createView()));
    }
    
    public function getTermineAction(Request $request) {
        $page = $request->get('page');
        $dataInizio = new DateTime('-3 days');
        $word = $request->get('word');

        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('AdisIwatchyouBundle:Tweet');
        
        $wordWithSpaces = ' '.$word.' ';
        
        $tweets = $repository->findTweetsWithWordPaginator($dataInizio, $wordWithSpaces, $this->maxResults, $page);
        
        $totalPages = ceil(count($tweets) / $this->maxResults);
        
        $html = null;
        $i = 0;
        foreach ($tweets as $tweet) {
            if($i % 2 == 0)
                $html .='<div class="row-fluid">';

            $html .='<div class="span6">
                    <div class="clearfix timeline-tweet">     

                    <div class="span2">
                        <a href="../parlamentare/'. $tweet->getIdPolitico()->getId().'">
                        <img src="'.$tweet->getIdPolitico()->getProfileImage().'" class="img-polaroid"></img></a>
                    </div>
                    <div class="span10">
                        <div class="tweet-body">
                            <a href="../parlamentare/'. $tweet->getIdPolitico()->getId().'"><h4 class="name-custom">'. $tweet->getIdPolitico()->getNome().' '. $tweet->getIdPolitico()->getCognome().'</h4></a>
                            <div class="tweet-partito">'. $tweet->getIdPolitico()->getGruppo().'</div>
                            <div><p>'.$this->formatUrlsInTweet($tweet->getTesto()).'</p></div>
                            <ul class="inline"><li><a href="https://twitter.com/intent/tweet?in_reply_to='.$tweet->getIdStr().'&hashtags=tweetparlamento"><i class="icon-reply"></i> Risposta</a></li><li><a href="https://twitter.com/intent/retweet?tweet_id='. $tweet->getIdStr().'"><i class="icon-retweet"></i> Retweet</a></li><li><a href="https://twitter.com/intent/favorite?tweet_id='. $tweet->getIdStr().'"><i class="icon-star"></i> Preferiti</a></li></ul>
                            <div class="tweet-data">'. $tweet->getData()->format('d-m-Y H:i:s').'</div>
                        </div>
                    </div>
                </div>
            </div>';
            if($i % 2 != 0)
                $html .= '</div>';
            $i++;
        }
        
        if(($i % 2 != 0) && ($i < $this->maxResults))
                $html .= '</div>';
        
        if($page == $totalPages)
                $html .= '<div id="end"></div>';
        return new Response($html, 200, array('Content-Type'=>'text/html'));
    }    

}
