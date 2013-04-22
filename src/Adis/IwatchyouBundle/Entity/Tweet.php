<?php

namespace Adis\IwatchyouBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Tweet
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="Adis\IwatchyouBundle\Entity\TweetRepository")
 */
class Tweet
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Politico", inversedBy="tweet")
     * @ORM\JoinColumn(name="idPolitico", referencedColumnName="id")
     */
    private $idPolitico;

    /**
     * @ORM\Column(type="datetime")
     */
    private $data;
    
    /**
     * @ORM\Column(type="string", length=255)
     */
    private $idStr;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $testo;

    
    /**
     * @ORM\Column(type="text")
     */
    private $wordCloud;
    
    /**
     * @ORM\Column(type="integer")
     */
    private $numReplies;
    
    /**
     * @ORM\Column(type="integer")
     */
    private $numRetweet;


    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set data
     *
     * @param \DateTime $data
     * @return Tweet
     */
    public function setData($data)
    {
        $this->data = $data;
    
        return $this;
    }

    /**
     * Get data
     *
     * @return \DateTime 
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Set idStr
     *
     * @param string $idStr
     * @return Tweet
     */
    public function setIdStr($idStr)
    {
        $this->idStr = $idStr;
    
        return $this;
    }

    /**
     * Get idStr
     *
     * @return string 
     */
    public function getIdStr()
    {
        return $this->idStr;
    }

    /**
     * Set testo
     *
     * @param string $testo
     * @return Tweet
     */
    public function setTesto($testo)
    {
        $this->testo = $testo;
    
        return $this;
    }

    /**
     * Get testo
     *
     * @return string 
     */
    public function getTesto()
    {
        return $this->testo;
    }

    /**
     * Set idPolitico
     *
     * @param \Adis\IwatchyouBundle\Entity\Politico $idPolitico
     * @return Tweet
     */
    public function setIdPolitico(\Adis\IwatchyouBundle\Entity\Politico $idPolitico = null)
    {
        $this->idPolitico = $idPolitico;
    
        return $this;
    }

    /**
     * Get idPolitico
     *
     * @return \Adis\IwatchyouBundle\Entity\Politico 
     */
    public function getIdPolitico()
    {
        return $this->idPolitico;
    }

    /**
     * Set wordCloud
     *
     * @param string $wordCloud
     * @return Tweet
     */
    public function setWordCloud($wordCloud)
    {
        $this->wordCloud = $wordCloud;
    
        return $this;
    }

    /**
     * Get wordCloud
     *
     * @return string 
     */
    public function getWordCloud()
    {
        return $this->wordCloud;
    }

    /**
     * Set numReplies
     *
     * @param integer $numReplies
     * @return Tweet
     */
    public function setNumReplies($numReplies)
    {
        $this->numReplies = $numReplies;
    
        return $this;
    }

    /**
     * Get numReplies
     *
     * @return integer 
     */
    public function getNumReplies()
    {
        return $this->numReplies;
    }

    /**
     * Set numRetweet
     *
     * @param integer $numRetweet
     * @return Tweet
     */
    public function setNumRetweet($numRetweet)
    {
        $this->numRetweet = $numRetweet;
    
        return $this;
    }

    /**
     * Get numRetweet
     *
     * @return integer 
     */
    public function getNumRetweet()
    {
        return $this->numRetweet;
    }
}