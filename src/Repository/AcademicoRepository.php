<?php

namespace App\Repository;

use App\Entity\Academico;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Academico|null find($id, $lockMode = null, $lockVersion = null)
 * @method Academico|null findOneBy(array $criteria, array $orderBy = null)
 * @method Academico[]    findAll()
 * @method Academico[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AcademicoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Academico::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(Academico $entity, bool $flush = true): void
    {
        $this->_em->persist($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(Academico $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    public function cuentaVotos($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.voto = :val')
            ->setParameter('val', $value)
            ->select('COUNT(c.voto) as votos')
            ->getQuery()
            ->getSingleScalarResult();
    }

    // /**
    //  * @return Academico[] Returns an array of Academico objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('a.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Academico
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
