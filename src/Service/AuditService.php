<?php

namespace App\Service;

use App\Entity\AuditLog;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Security;

class AuditService
{
    private EntityManagerInterface $entityManager;
    private RequestStack $requestStack;
    private Security $security;

    public function __construct(
        EntityManagerInterface $entityManager,
        RequestStack $requestStack,
        Security $security
    ) {
        $this->entityManager = $entityManager;
        $this->requestStack = $requestStack;
        $this->security = $security;
    }

    /**
     * Enregistre une action dans l'audit log
     */
    public function log(
        string $action,
        string $entityType,
        ?int $entityId = null,
        ?string $description = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?User $user = null
    ): AuditLog {
        $auditLog = new AuditLog();
        $auditLog->setAction($action);
        $auditLog->setEntityType($entityType);
        $auditLog->setEntityId($entityId);
        $auditLog->setDescription($description);
        $auditLog->setOldValues($oldValues);
        $auditLog->setNewValues($newValues);

        // Utilisateur qui fait l'action
        $currentUser = $user ?? $this->security->getUser();
        if ($currentUser instanceof User) {
            $auditLog->setUser($currentUser);
        }

        // Informations de la requête
        $request = $this->requestStack->getCurrentRequest();
        if ($request) {
            $auditLog->setIpAddress($request->getClientIp());
            $auditLog->setUserAgent($request->headers->get('User-Agent'));
        }

        $this->entityManager->persist($auditLog);
        $this->entityManager->flush();

        return $auditLog;
    }

    /**
     * Log de création d'entité
     */
    public function logCreate(object $entity, ?string $description = null): AuditLog
    {
        return $this->log(
            'create',
            $this->getEntityType($entity),
            $this->getEntityId($entity),
            $description ?? "Création de " . $this->getEntityType($entity),
            null,
            $this->extractEntityValues($entity)
        );
    }

    /**
     * Log de modification d'entité
     */
    public function logUpdate(object $entity, array $oldValues, ?string $description = null): AuditLog
    {
        return $this->log(
            'update',
            $this->getEntityType($entity),
            $this->getEntityId($entity),
            $description ?? "Modification de " . $this->getEntityType($entity),
            $oldValues,
            $this->extractEntityValues($entity)
        );
    }

    /**
     * Log de suppression d'entité
     */
    public function logDelete(object $entity, ?string $description = null): AuditLog
    {
        return $this->log(
            'delete',
            $this->getEntityType($entity),
            $this->getEntityId($entity),
            $description ?? "Suppression de " . $this->getEntityType($entity),
            $this->extractEntityValues($entity),
            null
        );
    }

    /**
     * Log d'approbation de prêt
     */
    public function logLoanApproval(object $loan): AuditLog
    {
        return $this->log(
            'approve',
            'Loan',
            $this->getEntityId($loan),
            "Approbation du prêt #" . $this->getEntityId($loan)
        );
    }

    /**
     * Log de rejet de prêt
     */
    public function logLoanRejection(object $loan): AuditLog
    {
        return $this->log(
            'reject',
            'Loan',
            $this->getEntityId($loan),
            "Rejet du prêt #" . $this->getEntityId($loan)
        );
    }

    /**
     * Log d'activation/désactivation d'utilisateur
     */
    public function logUserStatusChange(object $user, bool $isActivation): AuditLog
    {
        $action = $isActivation ? 'activate' : 'deactivate';
        $description = $isActivation ? 'Activation' : 'Désactivation';
        
        return $this->log(
            $action,
            'User',
            $this->getEntityId($user),
            "$description de l'utilisateur " . $user->getEmail()
        );
    }

    /**
     * Log d'upload de document
     */
    public function logMediaUpload(object $media): AuditLog
    {
        return $this->log(
            'upload',
            'Media',
            $this->getEntityId($media),
            "Upload du document " . $media->getOriginalName()
        );
    }

    /**
     * Log de téléchargement de document
     */
    public function logMediaDownload(object $media): AuditLog
    {
        return $this->log(
            'download',
            'Media',
            $this->getEntityId($media),
            "Téléchargement du document " . $media->getOriginalName()
        );
    }

    /**
     * Log d'envoi de notification
     */
    public function logNotificationSent(object $notification): AuditLog
    {
        return $this->log(
            'send',
            'Notification',
            $this->getEntityId($notification),
            "Envoi de notification : " . $notification->getSubject()
        );
    }

    /**
     * Log de connexion utilisateur
     */
    public function logUserLogin(User $user): AuditLog
    {
        return $this->log(
            'login',
            'User',
            $user->getId(),
            "Connexion de l'utilisateur " . $user->getEmail(),
            null,
            null,
            $user
        );
    }

    /**
     * Log de déconnexion utilisateur
     */
    public function logUserLogout(User $user): AuditLog
    {
        return $this->log(
            'logout',
            'User',
            $user->getId(),
            "Déconnexion de l'utilisateur " . $user->getEmail(),
            null,
            null,
            $user
        );
    }

    /**
     * Extrait le type d'entité
     */
    private function getEntityType(object $entity): string
    {
        $reflection = new \ReflectionClass($entity);
        return $reflection->getShortName();
    }

    /**
     * Extrait l'ID de l'entité
     */
    private function getEntityId(object $entity): ?int
    {
        if (method_exists($entity, 'getId')) {
            return $entity->getId();
        }
        return null;
    }

    /**
     * Extrait les valeurs importantes de l'entité pour l'audit
     */
    private function extractEntityValues(object $entity): array
    {
        $values = [];
        $reflection = new \ReflectionClass($entity);
        
        foreach ($reflection->getProperties() as $property) {
            $property->setAccessible(true);
            $value = $property->getValue($entity);
            
            // On évite de stocker les objets complexes
            if (is_scalar($value) || is_null($value) || $value instanceof \DateTime) {
                $values[$property->getName()] = $value instanceof \DateTime ? $value->format('Y-m-d H:i:s') : $value;
            }
        }
        
        return $values;
    }

    /**
     * Récupère l'historique d'une entité
     */
    public function getEntityHistory(string $entityType, int $entityId): array
    {
        return $this->entityManager->getRepository(AuditLog::class)
            ->findByEntity($entityType, $entityId);
    }

    /**
     * Récupère l'activité récente d'un utilisateur
     */
    public function getUserActivity(int $userId, int $limit = 50): array
    {
        return $this->entityManager->getRepository(AuditLog::class)
            ->findByUser($userId, $limit);
    }
}
