<?php

namespace App\Controller\Client;

use App\Entity\Message;
use App\Entity\User;
use App\Form\MessageType;
use App\Repository\MessageRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/client/messages')]
#[IsGranted('ROLE_USER')]
class MessageController extends AbstractController
{
    public function __construct(
        private MessageRepository $messageRepository,
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('/', name: 'client_messages')]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Récupérer toutes les conversations
        $conversations = $this->messageRepository->findConversations($user);
        
        // Récupérer les administrateurs pour pouvoir envoyer des messages
        $admins = $this->userRepository->findByRole('ROLE_ADMIN');

        return $this->render('client/messages/index.html.twig', [
            'conversations' => $conversations,
            'admins' => $admins
        ]);
    }

    #[Route('/conversation/{userId}', name: 'client_messages_conversation')]
    public function conversation(int $userId): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $recipient = $this->userRepository->find($userId);

        if (!$recipient) {
            throw $this->createNotFoundException('Utilisateur non trouvé');
        }

        // Récupérer les messages de la conversation
        $messages = $this->messageRepository->findConversationMessages($user, $recipient);
        
        // Marquer les messages reçus comme lus
        foreach ($messages as $message) {
            if ($message->getRecipient() === $user && !$message->isRead()) {
                $message->setIsRead(true);
            }
        }
        $this->entityManager->flush();

        // Créer le formulaire pour envoyer un nouveau message
        $newMessage = new Message();
        $form = $this->createForm(MessageType::class, $newMessage);

        return $this->render('client/messages/conversation.html.twig', [
            'recipient' => $recipient,
            'messages' => $messages,
            'form' => $form->createView()
        ]);
    }

    #[Route('/send/{userId}', name: 'client_messages_send', methods: ['POST'])]
    public function send(Request $request, int $userId): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $recipient = $this->userRepository->find($userId);

        if (!$recipient) {
            throw $this->createNotFoundException('Utilisateur non trouvé');
        }

        $message = new Message();
        $form = $this->createForm(MessageType::class, $message);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $message->setSender($user);
            $message->setRecipient($recipient);
            $message->setSentAt(new \DateTime());
            $message->setIsRead(false);

            $this->entityManager->persist($message);
            $this->entityManager->flush();

            $this->addFlash('success', 'Message envoyé avec succès');
        } else {
            $this->addFlash('error', 'Erreur lors de l\'envoi du message');
        }

        return $this->redirectToRoute('client_messages_conversation', ['userId' => $userId]);
    }

    #[Route('/api/unread-count', name: 'api_messages_unread_count', methods: ['GET'])]
    public function getUnreadCount(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $count = $this->messageRepository->countUnreadMessages($user);

        return new JsonResponse(['count' => $count]);
    }

    #[Route('/api/new-messages', name: 'api_messages_new', methods: ['GET'])]
    public function getNewMessages(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $messages = $this->messageRepository->findBy([
            'recipient' => $user,
            'isRead' => false
        ], ['sentAt' => 'DESC'], 5);

        $messagesData = array_map(function($message) {
            return [
                'id' => $message->getId(),
                'subject' => $message->getSubject(),
                'content' => substr($message->getContent(), 0, 100) . '...',
                'sender' => $message->getSender()->getFullName(),
                'sent_at' => $message->getSentAt()->format('Y-m-d H:i:s')
            ];
        }, $messages);

        return new JsonResponse(['messages' => $messagesData]);
    }

    #[Route('/api/conversation/{userId}/messages', name: 'api_conversation_messages', methods: ['GET'])]
    public function getConversationMessages(int $userId): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $recipient = $this->userRepository->find($userId);

        if (!$recipient) {
            return new JsonResponse(['error' => 'Utilisateur non trouvé'], 404);
        }

        $messages = $this->messageRepository->findConversationMessages($user, $recipient);
        
        $messagesData = array_map(function($message) {
            return [
                'id' => $message->getId(),
                'subject' => $message->getSubject(),
                'content' => $message->getContent(),
                'sender_id' => $message->getSender()->getId(),
                'sender_name' => $message->getSender()->getFullName(),
                'sent_at' => $message->getSentAt()->format('Y-m-d H:i:s'),
                'is_read' => $message->isRead()
            ];
        }, $messages);

        return new JsonResponse(['messages' => $messagesData]);
    }
}