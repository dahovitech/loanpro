<?php

namespace App\Entity;

use App\Repository\LoanRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LoanRepository::class)]
class Loan
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'loans')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private ?string $amount = null;

    #[ORM\Column(type: 'decimal', precision: 5, scale: 2)]
    private ?string $interestRate = null;

    #[ORM\Column]
    private ?int $duration = null; // en mois

    #[ORM\Column(length: 20)]
    private ?string $status = null; // pending, approved, rejected, active, completed

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $purpose = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $monthlyPayment = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $totalAmount = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $approvedAt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $disbursedAt = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $rejectionReason = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->status = 'pending';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): static
    {
        $this->amount = $amount;
        return $this;
    }

    public function getInterestRate(): ?string
    {
        return $this->interestRate;
    }

    public function setInterestRate(string $interestRate): static
    {
        $this->interestRate = $interestRate;
        return $this;
    }

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function setDuration(int $duration): static
    {
        $this->duration = $duration;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getPurpose(): ?string
    {
        return $this->purpose;
    }

    public function setPurpose(?string $purpose): static
    {
        $this->purpose = $purpose;
        return $this;
    }

    public function getMonthlyPayment(): ?string
    {
        return $this->monthlyPayment;
    }

    public function setMonthlyPayment(?string $monthlyPayment): static
    {
        $this->monthlyPayment = $monthlyPayment;
        return $this;
    }

    public function getTotalAmount(): ?string
    {
        return $this->totalAmount;
    }

    public function setTotalAmount(?string $totalAmount): static
    {
        $this->totalAmount = $totalAmount;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getApprovedAt(): ?\DateTimeInterface
    {
        return $this->approvedAt;
    }

    public function setApprovedAt(?\DateTimeInterface $approvedAt): static
    {
        $this->approvedAt = $approvedAt;
        return $this;
    }

    public function getDisbursedAt(): ?\DateTimeInterface
    {
        return $this->disbursedAt;
    }

    public function setDisbursedAt(?\DateTimeInterface $disbursedAt): static
    {
        $this->disbursedAt = $disbursedAt;
        return $this;
    }

    public function getRejectionReason(): ?string
    {
        return $this->rejectionReason;
    }

    public function setRejectionReason(?string $rejectionReason): static
    {
        $this->rejectionReason = $rejectionReason;
        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;
        return $this;
    }

    public function calculateMonthlyPayment(): string
    {
        if (!$this->amount || !$this->interestRate || !$this->duration) {
            return '0.00';
        }

        $principal = (float) $this->amount;
        $monthlyRate = (float) $this->interestRate / 100 / 12;
        $months = $this->duration;

        if ($monthlyRate == 0) {
            $monthlyPayment = $principal / $months;
        } else {
            $monthlyPayment = $principal * ($monthlyRate * pow(1 + $monthlyRate, $months)) / (pow(1 + $monthlyRate, $months) - 1);
        }

        return number_format($monthlyPayment, 2, '.', '');
    }

    public function calculateTotalAmount(): string
    {
        $monthlyPayment = (float) $this->calculateMonthlyPayment();
        $totalAmount = $monthlyPayment * $this->duration;
        return number_format($totalAmount, 2, '.', '');
    }

    public function getStatusLabel(): string
    {
        return match($this->status) {
            'pending' => 'En attente',
            'approved' => 'Approuvé',
            'rejected' => 'Rejeté',
            'active' => 'Actif',
            'completed' => 'Terminé',
            default => 'Inconnu'
        };
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['approved', 'active']);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }
}
