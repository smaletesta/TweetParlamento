<?php

namespace Adis\IwatchyouBundle\Entity;

use Doctrine\ORM\EntityRepository;
use DateTime;

class TweetRepository extends EntityRepository {
    
    public function findTweetFromDate(DateTime $start){
        $em = $this->getEntityManager();
        $query = $em->createQuery('
            SELECT
                tweet.testo
            FROM
                AdisIwatchyouBundle:Tweet tweet
            WHERE
                tweet.data > :data')
            ->setParameter('data', $start);
        return $query->getResult();
    }
    
    public function findCloudFromDate(DateTime $start){
        $em = $this->getEntityManager();
        $query = $em->createQuery('
            SELECT
                tweet.wordCloud
            FROM
                AdisIwatchyouBundle:Tweet tweet
            WHERE
                tweet.data > :data')
            ->setParameter('data', $start);
        return $query->getResult();
    }
    
    public function findTweetByPoliticoFromDate(DateTime $start, $id){
        $em = $this->getEntityManager();
        $query = $em->createQuery('
            SELECT
                tweet.testo
            FROM
                AdisIwatchyouBundle:Tweet tweet
            WHERE
                tweet.data > :data
            AND
                tweet.idPolitico = :idPolitico')
            ->setParameters(array('data' => $start, 'idPolitico' => $id));
        return $query->getResult();
    }

    public function findCloudByPoliticoFromDate(DateTime $start, $id){
        $em = $this->getEntityManager();
        $query = $em->createQuery('
            SELECT
                tweet.wordCloud
            FROM
                AdisIwatchyouBundle:Tweet tweet
            WHERE
                tweet.data > :data
            AND
                tweet.idPolitico = :idPolitico')
            ->setParameters(array('data' => $start, 'idPolitico' => $id));
        return $query->getResult();
    }
    
    public function findTweetByPoliticiFromDate($nome, $ramo, $regione, $partito, $data){
        $qb = $this->createQueryBuilder('tweet')
            ->select('tweet.testo')
            ->innerJoin('tweet.idPolitico', 'politico');
        if($nome) {
            $qb->andWhere('CONCAT(CONCAT(politico.nome, \' \'), politico.cognome) LIKE :searchterm OR CONCAT(CONCAT(politico.cognome, \' \'), politico.nome) LIKE :searchterm')
                ->setParameter('searchterm', '%'.$nome.'%');
        }
        if($ramo) {
            $qb->andWhere('politico.ramo = :ramo')
                ->setParameter('ramo', $ramo);
        }
        if($regione) {
            $qb->andWhere('politico.circoscrizione = :regione')
                ->setParameter('regione', $regione);
        }
        if($partito) {
            $qb->andWhere('politico.gruppo = :partito')
                ->setParameter('partito', $partito);
        }
        $qb->andWhere('tweet.data > :data')
                ->setParameter('data', $data);
        return $qb->getQuery()->getResult();
    }
    
    public function findCloudByPoliticiFromDate($nome, $ramo, $regione, $partito, $data){
        $qb = $this->createQueryBuilder('tweet')
            ->select('tweet.wordCloud')
            ->innerJoin('tweet.idPolitico', 'politico');
        if($nome) {
            $qb->andWhere('CONCAT(CONCAT(politico.nome, \' \'), politico.cognome) LIKE :searchterm OR CONCAT(CONCAT(politico.cognome, \' \'), politico.nome) LIKE :searchterm')
                ->setParameter('searchterm', '%'.$nome.'%');
        }
        if($ramo) {
            $qb->andWhere('politico.ramo = :ramo')
                ->setParameter('ramo', $ramo);
        }
        if($regione) {
            $qb->andWhere('politico.circoscrizione = :regione')
                ->setParameter('regione', $regione);
        }
        if($partito) {
            $qb->andWhere('politico.gruppo = :partito')
                ->setParameter('partito', $partito);
        }
        $qb->andWhere('tweet.data > :data')
                ->setParameter('data', $data);
        return $qb->getQuery()->getResult();
    }
    
    public function getEngagementStatistics(DateTime $dataInizio, DateTime $dataFine, $idPolitico) {
        $qb = $this->createQueryBuilder('tweet')
            ->select('tweet')
            ->where('tweet.data >= :dataInizio')
            ->andWhere('tweet.data <= :dataFine')
            ->andWhere('tweet.idPolitico = :id')
            ->orderBy('tweet.data', 'DESC')
            ->setParameters(array('dataInizio' => $dataInizio, 'dataFine' => $dataFine, 'id' => $idPolitico))
            ->getQuery();
        return $qb->getResult();
    }
}

?>
