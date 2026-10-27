<?php

namespace App\Repository;

use App\DTO\CreateShiftRequest;
use App\Entity\Shift;
use App\Event\ShiftCreatedEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * @extends ServiceEntityRepository<Shift>
 */
class ShiftRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
        parent::__construct($registry, Shift::class);
    }

    public function saveFromRequest(CreateShiftRequest $createShiftRequest): Shift
    {
        $date = $createShiftRequest->getDate();

        $shift = (new Shift())
            ->setEmployeeId($createShiftRequest->getEmployeeId())
            ->setStart(new \DateTimeImmutable((string) $date->atTime($createShiftRequest->getStartTime())))
            ->setEnd(new \DateTimeImmutable((string) $date->atTime($createShiftRequest->getEndTime())))
            ->setCountryCode($createShiftRequest->getCountryCode());

        $this->save($shift);

        return $shift;
    }

    public function save(Shift $shift): Shift
    {
        $em = $this->getEntityManager();
        $em->persist($shift);
        $em->flush();

        $this->eventDispatcher->dispatch(new ShiftCreatedEvent($shift));

        return $shift;
    }

    public function hasOverlap(string $employeeId, \DateTimeImmutable $start, \DateTimeImmutable $end): bool
    {
        $count = $this->createQueryBuilder('s')
            ->select('COUNT(s.employeeId)')
            ->andWhere('s.employeeId = :employeeId')
            ->andWhere('s.start < :end')
            ->andWhere('s.end > :start')
            ->setParameter('employeeId', $employeeId)
            ->setParameter('start', $start, Types::DATETIME_IMMUTABLE)
            ->setParameter('end', $end, Types::DATETIME_IMMUTABLE)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }
}
