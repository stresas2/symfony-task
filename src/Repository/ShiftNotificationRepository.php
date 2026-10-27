<?php

namespace App\Repository;

use App\Entity\Shift;
use App\Entity\ShiftNotification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ShiftNotification>
 */
class ShiftNotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ShiftNotification::class);
    }

    public function saveFromShift(Shift $shift): ShiftNotification
    {
        $notification = (new ShiftNotification())
            ->setEmployeeId($shift->getEmployeeId())
            ->setData([
                'shift_id' => $shift->getId(),
                'start' => $shift->getStart()->format(\DATE_ATOM),
                'end' => $shift->getEnd()->format(\DATE_ATOM),
                'country_code' => $shift->getCountryCode(),
            ]);

        return $this->save($notification);
    }

    public function save(ShiftNotification $notification): ShiftNotification
    {
        $em = $this->getEntityManager();
        $em->persist($notification);
        $em->flush();

        return $notification;
    }
}
