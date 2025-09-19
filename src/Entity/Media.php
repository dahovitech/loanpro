<?php

namespace App\Entity;

use App\Repository\MediaRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MediaRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\Table(name: 'media')]
class Media
{
    use LogTrait;

    public const TYPE_IDENTITY = 'identity';
    public const TYPE_INCOME_PROOF = 'income_proof';
    public const TYPE_RESIDENCE_PROOF = 'residence_proof';
    public const TYPE_BANK_STATEMENT = 'bank_statement';
    public const TYPE_CONTRACT = 'contract';
    public const TYPE_OTHER = 'other';

    public const ALLOWED_TYPES = [
        self::TYPE_IDENTITY => 'Pièce d\'identité',
        self::TYPE_INCOME_PROOF => 'Justificatif de revenus',
        self::TYPE_RESIDENCE_PROOF => 'Justificatif de domicile',
        self::TYPE_BANK_STATEMENT => 'Relevé bancaire',
        self::TYPE_CONTRACT => 'Contrat',
        self::TYPE_OTHER => 'Autre document',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $fileName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $originalFileName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $alt = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $extension = null;

    #[ORM\Column(length: 50)]
    private string $type = self::TYPE_OTHER;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $fileSize = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $mimeType = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isValidated = false;

    #[ORM\Column(type: 'string', length: 50)]
    private string $status = 'pending';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToMany(targetEntity: Loan::class, mappedBy: 'documents')]
    private Collection $loans;

    /**
     * @var File|null
     */
    #[Assert\File(
        maxSize: '10M',
        mimeTypes: [
            'application/pdf',
            'image/jpeg',
            'image/png',
            'image/gif',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ],
        mimeTypesMessage: 'Veuillez télécharger un fichier valide (PDF, DOC, DOCX, JPG, PNG, GIF)'
    )]
    private $file;

    private $tempFilename;

    public function __construct()
    {
        $this->loans = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAlt(): ?string
    {
        return $this->alt;
    }

    public function setAlt(?string $alt): self
    {
        $this->alt = $alt;
        return $this;
    }

    public function getExtension(): ?string
    {
        return $this->extension;
    }

    public function setExtension(?string $extension): self
    {
        $this->extension = $extension;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        if (!in_array($type, array_keys(self::ALLOWED_TYPES))) {
            throw new \InvalidArgumentException("Type de document invalide: {$type}");
        }
        $this->type = $type;
        return $this;
    }

    public function getTypeLabel(): string
    {
        return self::ALLOWED_TYPES[$this->type] ?? 'Inconnu';
    }

    public function getFileSize(): ?int
    {
        return $this->fileSize;
    }

    public function setFileSize(?int $fileSize): self
    {
        $this->fileSize = $fileSize;
        return $this;
    }

    public function getFileSizeFormatted(): string
    {
        if (!$this->fileSize) {
            return 'Taille inconnue';
        }

        $bytes = $this->fileSize;
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' bytes';
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function setMimeType(?string $mimeType): self
    {
        $this->mimeType = $mimeType;
        return $this;
    }

    public function isValidated(): bool
    {
        return $this->isValidated;
    }

    public function setIsValidated(bool $isValidated): self
    {
        $this->isValidated = $isValidated;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getOriginalFileName(): ?string
    {
        return $this->originalFileName;
    }

    public function setOriginalFileName(?string $originalFileName): self
    {
        $this->originalFileName = $originalFileName;
        return $this;
    }

    /**
     * @return Collection<int, Loan>
     */
    public function getLoans(): Collection
    {
        return $this->loans;
    }

    public function addLoan(Loan $loan): self
    {
        if (!$this->loans->contains($loan)) {
            $this->loans->add($loan);
            $loan->addDocument($this);
        }
        return $this;
    }

    public function removeLoan(Loan $loan): self
    {
        if ($this->loans->removeElement($loan)) {
            $loan->removeDocument($this);
        }
        return $this;
    }

    public function setFile(?File $file): self
    {
        $this->file = $file;
        
        if (null !== $this->fileName) {
            $this->tempFilename = $this->fileName;
            $this->fileName = null;
            $this->alt = null;
            $this->extension = null;
        }
        
        return $this;
    }

    public function getFile(): ?File
    {
        return $this->file;
    }

    #[ORM\PrePersist()]
    #[ORM\PreUpdate()]
    public function preUpload(): void
    {
        if (null === $this->file) {
            return;
        }

        // Sauvegarder le nom original
        $this->originalFileName = $this->file->getClientOriginalName();
        
        // Générer un nom unique
        if ($this->file->guessExtension()) {
            $this->fileName = uniqid() . '.' . $this->file->guessExtension();
            $this->extension = $this->file->guessExtension();
        }

        // Métadonnées du fichier
        $this->fileSize = $this->file->getSize();
        $this->mimeType = $this->file->getMimeType();
        
        // Alt par défaut
        if (!$this->alt) {
            $this->alt = $this->originalFileName;
        }
    }

    #[ORM\PostPersist()]
    #[ORM\PostUpdate()]
    public function upload(): void
    {
        if (null === $this->file) {
            return;
        }

        // Supprimer l'ancien fichier s'il existe
        if (null !== $this->tempFilename) {
            $oldFile = $this->getUploadRootDir() . DIRECTORY_SEPARATOR . $this->tempFilename;
            if (file_exists($oldFile)) {
                unlink($oldFile);
            }
        }

        // Créer le répertoire s'il n'existe pas
        $uploadDir = $this->getUploadRootDir();
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Déplacer le fichier
        $this->file->move($uploadDir, $this->getFileName());
        $this->file = null;
    }

    #[ORM\PreRemove()]
    public function preRemoveUpload(): void
    {
        $this->tempFilename = $this->getUploadRootDir() . DIRECTORY_SEPARATOR . $this->fileName;
    }

    #[ORM\PostRemove()]
    public function removeUpload(): void
    {
        if ($this->tempFilename && file_exists($this->tempFilename)) {
            unlink($this->tempFilename);
        }
    }

    public function getUploadDir(): string
    {
        return join(DIRECTORY_SEPARATOR, ['upload', 'media']);
    }

    protected function getUploadRootDir(): string
    {
        return join(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', 'public', $this->getUploadDir()]);
    }

    public function getWebPath(): string
    {
        return '/' . $this->getUploadDir() . '/' . $this->getFileName();
    }

    public function getFileName(): ?string
    {
        return $this->fileName;
    }

    public function setFileName(?string $fileName): self
    {
        $this->fileName = $fileName;
        return $this;
    }

    public function getAbsolutePath(): string
    {
        return $this->getUploadRootDir() . DIRECTORY_SEPARATOR . $this->getFileName();
    }

    public function exists(): bool
    {
        return $this->fileName && file_exists($this->getAbsolutePath());
    }

    public function isImage(): bool
    {
        return in_array($this->extension, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
    }

    public function isPdf(): bool
    {
        return $this->extension === 'pdf';
    }

    public function isDocument(): bool
    {
        return in_array($this->extension, ['doc', 'docx', 'pdf', 'txt', 'rtf']);
    }

    public function getIconClass(): string
    {
        return match ($this->extension) {
            'pdf' => 'fas fa-file-pdf text-danger',
            'doc', 'docx' => 'fas fa-file-word text-primary',
            'jpg', 'jpeg', 'png', 'gif', 'webp' => 'fas fa-file-image text-success',
            'xls', 'xlsx' => 'fas fa-file-excel text-success',
            'zip', 'rar' => 'fas fa-file-archive text-warning',
            default => 'fas fa-file text-secondary'
        };
    }

    public function __toString(): string
    {
        return $this->originalFileName ?? $this->fileName ?? 'Document sans nom';
    }
}
