<?php

namespace Adis\IwatchyouBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Politico
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="Adis\IwatchyouBundle\Entity\PoliticoRepository")
 */
class Politico
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
     * @ORM\OneToMany(targetEntity="Tweet", mappedBy="idPolitico")
     */
    private $tweet;

    /**
     * @ORM\OneToMany(targetEntity="Account", mappedBy="idPolitico")
     */
    private $account;
    
    /**
     * @ORM\Column(type="string", length=50)
     */
    private $nome;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private $cognome;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private $ramo;
    
    /**
     * @ORM\Column(type="string", length=50)
     */
    private $gruppo;
    
    /**
     * @ORM\Column(type="string", length=50)
     */
    private $circoscrizione;
    
    /**
     * @ORM\Column(type="string", length=50)
     */
    private $screenname;

    /**
     * @ORM\Column(type="text")
     */
    private $idStr;
    
    /**
     * @ORM\Column(type="text")
     */
    private $profileImage;
    
    /**
     * @ORM\Column(type="text")
     */
    private $bio;
    
    /**
     * @ORM\Column(type="integer")
     */
    private $numFollower;
    
    public function __construct()
    {
        $this->tweet = new ArrayCollection();
        $this->account = new ArrayCollection();
        
    }

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
     * Set nome
     *
     * @param string $nome
     * @return Politico
     */
    public function setNome($nome)
    {
        $this->nome = $nome;
    
        return $this;
    }

    /**
     * Get nome
     *
     * @return string 
     */
    public function getNome()
    {
        return $this->nome;
    }

    /**
     * Set cognome
     *
     * @param string $cognome
     * @return Politico
     */
    public function setCognome($cognome)
    {
        $this->cognome = $cognome;
    
        return $this;
    }

    /**
     * Get cognome
     *
     * @return string 
     */
    public function getCognome()
    {
        return $this->cognome;
    }

    /**
     * Set ramo
     *
     * @param string $ramo
     * @return Politico
     */
    public function setRamo($ramo)
    {
        $this->ramo = $ramo;
    
        return $this;
    }

    /**
     * Get ramo
     *
     * @return string 
     */
    public function getRamo()
    {
        return $this->ramo;
    }

    /**
     * Set gruppo
     *
     * @param string $gruppo
     * @return Politico
     */
    public function setGruppo($gruppo)
    {
        $this->gruppo = $gruppo;
    
        return $this;
    }

    /**
     * Get gruppo
     *
     * @return string 
     */
    public function getGruppo()
    {
        return $this->gruppo;
    }

    /**
     * Set circoscrizione
     *
     * @param string $circoscrizione
     * @return Politico
     */
    public function setCircoscrizione($circoscrizione)
    {
        $this->circoscrizione = $circoscrizione;
    
        return $this;
    }

    /**
     * Get circoscrizione
     *
     * @return string 
     */
    public function getCircoscrizione()
    {
        return $this->circoscrizione;
    }

    /**
     * Set screenname
     *
     * @param string $screenname
     * @return Politico
     */
    public function setScreenname($screenname)
    {
        $this->screenname = $screenname;
    
        return $this;
    }

    /**
     * Get screenname
     *
     * @return string 
     */
    public function getScreenname()
    {
        return $this->screenname;
    }

    /**
     * Set idStr
     *
     * @param string $idStr
     * @return Politico
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
     * Set profileImage
     *
     * @param string $profileImage
     * @return Politico
     */
    public function setProfileImage($profileImage)
    {
        $this->profileImage = $profileImage;
    
        return $this;
    }

    /**
     * Get profileImage
     *
     * @return string 
     */
    public function getProfileImage()
    {
        return $this->profileImage;
    }

    /**
     * Set bio
     *
     * @param string $bio
     * @return Politico
     */
    public function setBio($bio)
    {
        $this->bio = $bio;
    
        return $this;
    }

    /**
     * Get bio
     *
     * @return string 
     */
    public function getBio()
    {
        return $this->bio;
    }

    /**
     * Add tweet
     *
     * @param \Adis\IwatchyouBundle\Entity\Tweet $tweet
     * @return Politico
     */
    public function addTweet(\Adis\IwatchyouBundle\Entity\Tweet $tweet)
    {
        $this->tweet[] = $tweet;
    
        return $this;
    }

    /**
     * Remove tweet
     *
     * @param \Adis\IwatchyouBundle\Entity\Tweet $tweet
     */
    public function removeTweet(\Adis\IwatchyouBundle\Entity\Tweet $tweet)
    {
        $this->tweet->removeElement($tweet);
    }

    /**
     * Get tweet
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getTweet()
    {
        return $this->tweet;
    }

    /**
     * Add account
     *
     * @param \Adis\IwatchyouBundle\Entity\Account $account
     * @return Politico
     */
    public function addAccount(\Adis\IwatchyouBundle\Entity\Account $account)
    {
        $this->account[] = $account;
    
        return $this;
    }

    /**
     * Remove account
     *
     * @param \Adis\IwatchyouBundle\Entity\Account $account
     */
    public function removeAccount(\Adis\IwatchyouBundle\Entity\Account $account)
    {
        $this->account->removeElement($account);
    }

    /**
     * Get account
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getAccount()
    {
        return $this->account;
    }

    /**
     * Set numFollower
     *
     * @param integer $numFollower
     * @return Politico
     */
    public function setNumFollower($numFollower)
    {
        $this->numFollower = $numFollower;
    
        return $this;
    }

    /**
     * Get numFollower
     *
     * @return integer 
     */
    public function getNumFollower()
    {
        return $this->numFollower;
    }
}