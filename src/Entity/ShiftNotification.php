<?php

namespace App\Entity;

use App\Repository\ShiftNotificationRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: ShiftNotificationRepository::class)]
#[ORM\Table(name: 'shift_notification')]
#[ORM\Index(columns: ['employee_id'], name: 'idx_shift_notification_employee_id')]
class ShiftNotification
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(name: 'employee_id', type: 'string', length: 255)]
    private string $employeeId;

    /**
     * Snapshot of whatever the notification is about, e.g. the created shift.
     *
     * @var array<string, mixed>
     */
    #[ORM\Column(type: 'json')]
    private array $data = [];

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->id = (string) Uuid::v7();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getEmployeeId(): string
    {
        return $this->employeeId;
    }

    public function setEmployeeId(string $employeeId): static
    {
        $this->employeeId = $employeeId;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
