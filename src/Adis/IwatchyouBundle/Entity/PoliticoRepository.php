<?php

namespace Adis\IwatchyouBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;

class PoliticoRepository extends EntityRepository {
    
    public function findParlamentariCount($nome, $ramo, $regione, $partito){
        $qb = $this->createQueryBuilder('politico')
            ->select('COUNT(politico)');
        if($nome) {
            $qb->andWhere('CONCAT(CONCAT(politico.nome, \' \'), politico.cognome) LIKE :searchterm OR CONCAT(CONCAT(politico.cognome, \' \'), politico.nome) LIKE :searchterm')
//            $qb->andWhere('politico.nome LIKE :searchterm OR politico.cognome LIKE :searchterm')
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
        return $qb->getQuery()->getSingleScalarResult();
    }
    
    public function findParlamentariPaginator($nome, $ramo, $regione, $partito, $maxResults, $page) {
        $firstResult = ($page - 1) * $maxResults;
//        $queryMaxDate = $this->createQuery('SELECT MAX(account.data) FROM AdisIwatchyouBundle:Account account');
//        $date = $queryMaxDate->getResult();
        $qb = $this->createQueryBuilder('politico');
//                ->from('AdisIwatchyouBundle:Politico', 'politico')
//                ->join('account.idPolitico', 'politico');
        if($nome) {
            $qb->andWhere('CONCAT(CONCAT(politico.nome, \' \'), politico.cognome) LIKE :searchterm OR CONCAT(CONCAT(politico.cognome, \' \'), politico.nome) LIKE :searchterm')
//            $qb->andWhere('politico.nome LIKE :searchterm OR politico.cognome LIKE :searchterm')
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
//        $qb->andWhere('account.data = :data')
//                ->setParameter('data', $date)
//                ->orderBy('account.numFollower', 'DESC');
        $qb->orderBy('politico.numFollower', 'DESC');
        $query = $qb->getQuery()
            ->setFirstResult($firstResult)
            ->setMaxResults($maxResults);
        return new Paginator($query, $fetchJoinCollection = false);
    }

    public function findAllParlamentari($maxResults, $page) {
        $firstResult = ($page - 1) * $maxResults;
        $em = $this->getEntityManager();
        $dql = 'SELECT politico
                FROM AdisIwatchyouBundle:Politico politico
                ORDER BY politico.numFollower DESC';
        $query = $em->createQuery($dql)
            ->setFirstResult($firstResult)
            ->setMaxResults($maxResults);
        return new Paginator($query, $fetchJoinCollection = false);
    }
    
    public function findAllParlamentariCount() {
        $em = $this->getEntityManager();
        $dql = 'SELECT COUNT(politico) FROM AdisIwatchyouBundle:Politico politico';
        return $em->createQuery($dql)->getSingleScalarResult();
    }
    
}

?>
