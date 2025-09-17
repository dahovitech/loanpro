<?php

namespace App\Entity;

use App\Repository\LanguageRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: LanguageRepository::class)]
#[ORM\Table(name: 'languages')]
class Language
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 10, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 10)]
    private string $code;

    #[ORM\Column(type: 'string', length: 100)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 100)]
    private string $name;

    #[ORM\Column(type: 'string', length: 100)]
    #[Assert\NotBlank]
    private string $nativeName;

    #[ORM\Column(type: 'boolean')]
    private bool $isActive = true;

    #[ORM\Column(type: 'boolean')]
    private bool $isDefault = false;

    #[ORM\Column(type: 'integer')]
    private int $sortOrder = 0;

    #[ORM\OneToMany(mappedBy: 'language', targetEntity: ServiceTranslation::class, cascade: ['remove'])]
    private Collection $serviceTranslations;

    public function __construct()
    {
        $this->serviceTranslations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getNativeName(): string
    {
        return $this->nativeName;
    }

    public function setNativeName(string $nativeName): static
    {
        $this->nativeName = $nativeName;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function isDefault(): bool
    {
        return $this->isDefault;
    }

    public function setIsDefault(bool $isDefault): static
    {
        $this->isDefault = $isDefault;
        return $this;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): static
    {
        $this->sortOrder = $sortOrder;
        return $this;
    }

    /**
     * @return Collection<int, ServiceTranslation>
     */
    public function getServiceTranslations(): Collection
    {
        return $this->serviceTranslations;
    }

    public function addServiceTranslation(ServiceTranslation $serviceTranslation): static
    {
        if (!$this->serviceTranslations->contains($serviceTranslation)) {
            $this->serviceTranslations->add($serviceTranslation);
            $serviceTranslation->setLanguage($this);
        }

        return $this;
    }

    public function removeServiceTranslation(ServiceTranslation $serviceTranslation): static
    {
        if ($this->serviceTranslations->removeElement($serviceTranslation)) {
            if ($serviceTranslation->getLanguage() === $this) {
                $serviceTranslation->setLanguage(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
