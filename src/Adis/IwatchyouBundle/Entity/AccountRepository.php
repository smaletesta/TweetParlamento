<?php

namespace Adis\IwatchyouBundle\Entity;

use Doctrine\ORM\EntityRepository;
use DateTime;

class AccountRepository extends EntityRepository {
    
    public function findMostFollowed($maxResults){
        $em = $this->getEntityManager();
        $queryMaxDate = $em->createQuery('SELECT MAX(account.data) FROM AdisIwatchyouBundle:Account account');
        $date = $queryMaxDate->getResult();
        $queryMostFollowed = $em->createQuery('
            SELECT 
                politico.nome,
                politico.cognome,
                politico.profileImage,
                politico.id,
                account.numFollower
            FROM
                AdisIwatchyouBundle:Account account
            JOIN
                account.idPolitico politico
            WHERE
                account.data = :data
            ORDER BY
                account.numFollower DESC')
                //->setParameter('data', new DateTime('yesterday midnight'))
                ->setParameter('data', $date)
                ->setMaxResults($maxResults);
        return $queryMostFollowed->getResult();
    }
    
    public function findMostActive($maxResults) {
        $em = $this->getEntityManager();
        $queryMaxDate = $em->createQuery('SELECT MAX(account.data) FROM AdisIwatchyouBundle:Account account');
        $date = $queryMaxDate->getResult();
        $queryMostActive = $em->createQuery('
            SELECT 
                politico.nome,
                politico.cognome,
                politico.profileImage,
                politico.id,
                account.numTweet
            FROM
                AdisIwatchyouBundle:Account account
            JOIN
                account.idPolitico politico
            WHERE
                account.data = :data
            ORDER BY
                account.numTweet DESC')
                ->setParameter('data', $date)
                ->setMaxResults($maxResults);
        return $queryMostActive->getResult();
    }
    
    public function getStatistics(DateTime $dataInizio, DateTime $dataFine, $idPolitico) {
        $qb = $this->createQueryBuilder('account')
            ->select('account ')
            ->where('account.data >= :dataInizio')
            ->andWhere('account.data <= :dataFine')
            ->andWhere('account.idPolitico = :id')
            ->orderBy('account.data', 'DESC')
            ->setParameters(array('dataInizio' => $dataInizio, 'dataFine' => $dataFine, 'id' => $idPolitico))
            ->getQuery();
        return $qb->getResult();
    }
    
    public function getStatisticsDay($idPolitico) {
        $em = $this->getEntityManager();
        $queryMaxDate = $em->createQuery('SELECT MAX(account.data)
            FROM AdisIwatchyouBundle:Account account
            WHERE account.idPolitico = :id')
                ->setParameter('id', $idPolitico);
        $maxDate = $queryMaxDate->getResult();
        $qb = $this->createQueryBuilder('account')
            ->select('account ')
            ->where('account.data >= :giorno')
            ->andWhere('account.idPolitico = :id')
            ->orderBy('account.data', 'DESC')
            ->setParameters(array('giorno' => $maxDate, 'id' => $idPolitico))
            ->getQuery();
        return $qb->getResult();
    }
}

?>
