<?php

namespace App\Entity;

use App\Repository\ServiceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ServiceRepository::class)]
#[ORM\Table(name: 'services')]
class Service
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 255)]
    private string $slug;

    #[ORM\Column(type: 'boolean')]
    private bool $isActive = true;

    #[ORM\Column(type: 'integer')]
    private int $sortOrder = 0;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $updatedAt;

    #[ORM\OneToMany(mappedBy: 'service', targetEntity: ServiceTranslation::class, cascade: ['persist', 'remove'])]
    private Collection $translations;

    public function __construct()
    {
        $this->translations = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;
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

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): static
    {
        $this->sortOrder = $sortOrder;
        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    /**
     * @return Collection<int, ServiceTranslation>
     */
    public function getTranslations(): Collection
    {
        return $this->translations;
    }

    public function addTranslation(ServiceTranslation $translation): static
    {
        if (!$this->translations->contains($translation)) {
            $this->translations->add($translation);
            $translation->setService($this);
        }

        return $this;
    }

    public function removeTranslation(ServiceTranslation $translation): static
    {
        if ($this->translations->removeElement($translation)) {
            if ($translation->getService() === $this) {
                $translation->setService(null);
            }
        }

        return $this;
    }

    public function getTranslationForLanguage(Language $language): ?ServiceTranslation
    {
        foreach ($this->translations as $translation) {
            if ($translation->getLanguage() === $language) {
                return $translation;
            }
        }
        return null;
    }

    public function getTranslationForLanguageCode(string $languageCode): ?ServiceTranslation
    {
        foreach ($this->translations as $translation) {
            if ($translation->getLanguage()->getCode() === $languageCode) {
                return $translation;
            }
        }
        return null;
    }

    /**
     * Get the title for a specific language with fallback to default language
     */
    public function getTitle(string $languageCode, string $defaultLanguageCode = 'fr'): string
    {
        $translation = $this->getTranslationForLanguageCode($languageCode);
        if ($translation && $translation->getTitle()) {
            return $translation->getTitle();
        }

        // Fallback to default language
        if ($languageCode !== $defaultLanguageCode) {
            $defaultTranslation = $this->getTranslationForLanguageCode($defaultLanguageCode);
            if ($defaultTranslation && $defaultTranslation->getTitle()) {
                return $defaultTranslation->getTitle();
            }
        }

        return $this->slug; // Final fallback
    }

    public function __toString(): string
    {
        $firstTranslation = $this->translations->first();
        return $firstTranslation ? $firstTranslation->getTitle() ?? $this->slug : $this->slug;
    }
}
