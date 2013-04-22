<?php

namespace Adis\IwatchyouBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Account
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="Adis\IwatchyouBundle\Entity\AccountRepository")
 */
class Account {
    
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;
    
    /**
     * @ORM\ManyToOne(targetEntity="Politico", inversedBy="account")
     * @ORM\JoinColumn(name="idPolitico", referencedColumnName="id")
     */
    private $idPolitico;

    /**
     * @ORM\Column(type="datetime")
     */
    private $data;
    
    /**
     * @ORM\Column(type="integer")
     */
    private $numFollower;

    /**
     * @ORM\Column(type="integer")
     */
    private $numFollowing;

    /**
     * @ORM\Column(type="integer")
     */
    private $numTweet;

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
     * Set numFollower
     *
     * @param integer $numFollower
     * @return Account
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

    /**
     * Set numFollowing
     *
     * @param integer $numFollowing
     * @return Account
     */
    public function setNumFollowing($numFollowing)
    {
        $this->numFollowing = $numFollowing;
    
        return $this;
    }

    /**
     * Get numFollowing
     *
     * @return integer 
     */
    public function getNumFollowing()
    {
        return $this->numFollowing;
    }

    /**
     * Set numTweet
     *
     * @param integer $numTweet
     * @return Account
     */
    public function setNumTweet($numTweet)
    {
        $this->numTweet = $numTweet;
    
        return $this;
    }

    /**
     * Get numTweet
     *
     * @return integer 
     */
    public function getNumTweet()
    {
        return $this->numTweet;
    }

    /**
     * Set idPolitico
     *
     * @param \Adis\IwatchyouBundle\Entity\Politico $idPolitico
     * @return Account
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
     * Set data
     *
     * @param \DateTime $data
     * @return Account
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
}