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
                $cloud = array_slice($this->wordFrequency($tweets), 0, 30);
                $parlamentariCount = $repositoryPolitico->findParlamentariCount($data['nome'], $data['ramo'], $data['regione'], $data['partito']);
                if($parlamentariCount == 0) {
                    $session->getFlashBag()->add('error', 'La ricerca non ha prodotto risultati, prova a cercare qualcos\'altro');
                    return $this->redirect($this->generateUrl('adis_iwatchyou_homepage'));
                }
                if(count($cloud) == 0)
                    $session->getFlashBag()->add('notice', 'Negli ultimi tempi i Parlamentari che hai cercato non hanno twittato, perché non li inviti a farlo utilizzando il pulsante \'Twitta\' sotto la foto?');
                $totalPages = ceil($parlamentariCount / $this->maxResults);
                $end = false;
                if($totalPages == 1)
                    $end = true;
                $paginator = $repositoryPolitico->findParlamentariPaginator($data['nome'], $data['ramo'], $data['regione'], $data['partito'], $this->maxResults, 1);
                
                return $this->render('AdisIwatchyouBundle:Default:index.html.twig', array('cloud' => $cloud, 'mostFollowed' => $mostFollowed, 'mostActive' => $mostActive, 'parlamentari' => $paginator, 'end' => $end, 'location' => 'search', 'form' => $form->createView()));
            }
        }
        $tweets = $repositoryTweet->findCloudFromDate($dataInizio);
        $cloud = array_slice($this->wordFrequency($tweets), 0, 30);
        $count = $repositoryPolitico->findAllParlamentariCount();
        $totalPages = ceil($count / $this->maxResults);
        $end = false;
        if($totalPages == 1)
            $end = true;

        $paginator = $repositoryPolitico->findAllParlamentari($this->maxResults, 1);
        return $this->render('AdisIwatchyouBundle:Default:index.html.twig', array('cloud' => $cloud, 'mostFollowed' => $mostFollowed, 'mostActive' => $mostActive, 'parlamentari' => $paginator, 'end' => $end, 'location' => 'home', 'form' => $form->createView()));
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
        return $frequencyList;
    }

    public function searchParlamentariAction(Request $request) {
        $session = $this->get('session');
        $data = $session->get('data');
        $page = $request->get('page');
        $em = $this->getDoctrine()->getManager();
        
        $repository = $em->getRepository('AdisIwatchyouBundle:Politico');
        $parlamentariCount = $repository->findParlamentariCount($data['nome'], $data['ramo'], $data['regione'], $data['partito']);;
        $totalPages = ceil($parlamentariCount / $this->maxResults);
        $end = false;
        if($totalPages == 1)
            $end = true;
     
        $paginator = $repository->findParlamentariPaginator($data['nome'], $data['ramo'], $data['regione'], $data['partito'], $this->maxResults, $page);
        $html = null;
        $i = 0;
        foreach ($paginator as $parlamentare) {
            if($i % 2 == 0)
                $html .='<div class="row-fluid">';
            $html .=    '<div class="span6">
                            <div class="well clearfix">
                                <div class="span4">
                                    <a href="parlamentare/'.$parlamentare->getId().'"><img src="'.str_replace('_normal', '_bigger', $parlamentare->getProfileImage()).'" class="img-polaroid"></img></a>
                                    <a href="http://twitter.com/intent/tweet/?text=@'.$parlamentare->getScreenName().'&hashtags=tweetparlamento" class="btn btn-primary btn-custom"><i class="icon-twitter"></i> Twitta</a>
                                </div>
                                <div class="span8 nome-parlamentare">
                                    <a href="parlamentare/'.$parlamentare->getId().'"><h3 class="name-custom">'.$parlamentare->getNome().' '.$parlamentare->getCognome().'</h3></a>
                                    <div class="more-info"><em>'.$parlamentare->getGruppo().' - '.ucfirst($parlamentare->getRamo()).'. Regione d\'elezione: '.$parlamentare->getCircoscrizione().'</em></div>
                                    <div><p>'.$parlamentare->getBio().'</p></div>
                                </div>
                            </div>
                        </div>';
            if(($i % 2 != 0) || ($i == count($paginator) - 1))
                $html .= '</div>';
            $i++;
        }
        if($page == $totalPages)
            $html .= '<div id="end"></div>';
        return new Response($html, 200, array('Content-Type'=>'text/html'));
    }
    
    public function getParlamentariAction(Request $request) {
        $page = $request->get('page');
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('AdisIwatchyouBundle:Politico');
        $count = $repository->findAllParlamentariCount();
        $totalPages = ceil($count / $this->maxResults);
       
        $paginator = $repository->findAllParlamentari($this->maxResults, $page);
        $html = null;
        $i = 0;
        foreach ($paginator as $parlamentare) {
            if($i % 2 == 0)
                $html .='<div class="row-fluid">';
            $html .=    '<div class="span6">
                            <div class="well clearfix">
                                <div class="span4">
                                    <a href="parlamentare/'.$parlamentare->getId().'"><img src="'.str_replace('_normal', '_bigger', $parlamentare->getProfileImage()).'" class="img-polaroid"></img></a>
                                    <a href="http://twitter.com/intent/tweet/?text=@'.$parlamentare->getScreenName().'&hashtags=tweetparlamento" class="btn btn-primary btn-custom"><i class="icon-twitter"></i> Twitta</a>
                                </div>
                                <div class="span8 nome-parlamentare">
                                    <a href="parlamentare/'.$parlamentare->getId().'"><h3 class="name-custom">'.$parlamentare->getNome().' '.$parlamentare->getCognome().'</h3></a>
                                    <div class="more-info"><em>'.$parlamentare->getGruppo().' - '.ucfirst($parlamentare->getRamo()).'. Regione d\'elezione: '.$parlamentare->getCircoscrizione().'</em></div>
                                    <div><p>'.$parlamentare->getBio().'</p></div>
                                </div>
                            </div>
                        </div>';
            if(($i % 2 != 0) || ($i == count($paginator) - 1))
                $html .= '</div>';
            $i++;
        }
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
        $dataFine = new DateTime('now');
        $statistiche = $repositoryAccount->getStatisticsDay($politico->getId());
        $form = $this->createForm(new SearchParlamentariType());
        $mostFollowed = $repositoryAccount->findMostFollowed($this->maxResultsList);
        $mostActive = $repositoryAccount->findMostActive($this->maxResultsList);
        
        $dataInizioCloud = new DateTime('-15 days');
        $repositoryTweet = $em->getRepository('AdisIwatchyouBundle:Tweet');
        $tweets = $repositoryTweet->findCloudByPoliticoFromDate($dataInizioCloud, $id);
        $cloud = array_slice($this->wordFrequency($tweets), 0, 30);
        return $this->render('AdisIwatchyouBundle:Default:dettagli.html.twig', array('cloud' => $cloud, 'statistiche' => $statistiche, 'parlamentare' => $politico, 'form' => $form->createView(), 'mostFollowed' => $mostFollowed, 'mostActive' => $mostActive, 'dataInizio' => $dataInizio->format('Y-m-d'), 'dataFine' => $dataFine->format('Y-m-d')));
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
}