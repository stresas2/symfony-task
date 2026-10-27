<?php

namespace App\Entity;

use App\Repository\ShiftRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: ShiftRepository::class)]
#[ORM\Table(name: 'shift')]
#[ORM\Index(columns: ['employee_id', 'start_at', 'end_at'], name: 'idx_shift_employee_start_end')]
class Shift
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(name: 'employee_id', type: 'string', length: 255)]
    #[SerializedName('employee_id')]
    private string $employeeId;

    #[ORM\Column(name: 'start_at', type: 'datetime_immutable')]
    #[Context([DateTimeNormalizer::FORMAT_KEY => 'Y-m-d H:i'])]
    private \DateTimeImmutable $start;

    #[ORM\Column(name: 'end_at', type: 'datetime_immutable')]
    #[Context([DateTimeNormalizer::FORMAT_KEY => 'Y-m-d H:i'])]
    private \DateTimeImmutable $end;

    #[ORM\Column(name: 'country_code', type: 'string', length: 2)]
    #[SerializedName('country_code')]
    private string $countryCode;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    #[SerializedName('created_at')]
    #[Context([DateTimeNormalizer::FORMAT_KEY => \DATE_ATOM])]
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

    public function getStart(): \DateTimeImmutable
    {
        return $this->start;
    }

    public function setStart(\DateTimeImmutable $start): static
    {
        $this->start = $start;

        return $this;
    }

    public function getEnd(): \DateTimeImmutable
    {
        return $this->end;
    }

    public function setEnd(\DateTimeImmutable $end): static
    {
        $this->end = $end;

        return $this;
    }

    public function getCountryCode(): string
    {
        return $this->countryCode;
    }

    public function setCountryCode(string $countryCode): static
    {
        $this->countryCode = $countryCode;

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
