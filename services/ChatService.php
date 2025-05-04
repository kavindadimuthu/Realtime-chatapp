<?php

namespace app\services;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use app\models\Communication\ChatMessage;
use app\models\Communication\ChatRoom;
use app\models\Users\User;
use app\core\Helpers\AuthHelper;

class ChatService implements MessageComponentInterface
{
    protected $clients;
    protected $userConnections = [];
    protected $chatMessageModel;
    protected $chatRoomModel;
    protected $userModel;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
        $this->chatMessageModel = new ChatMessage();
        $this->chatRoomModel = new ChatRoom();
        $this->userModel = new User();
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);
        $conn->authenticated = false;
        $conn->userId = null;
        $conn->send(json_encode(['type' => 'auth_required']));
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $data = json_decode($msg, true);

        // Handle authentication first
        if (!$from->authenticated) {
            if (isset($data['type']) && $data['type'] === 'auth' && !empty($data['token'])) {
                $userId = AuthHelper::authenticate($data['token']);
                if ($userId) {
                    $from->authenticated = true;
                    $from->userId = $userId;
                    $this->userConnections[$userId] = $from;
                    $from->send(json_encode(['type' => 'auth_success', 'userId' => $userId]));

                    // Broadcast online status to relevant users
                    $this->broadcastUserStatus($userId, 'online');

                    // Send conversation list after authentication
                    $this->sendConversationsList($from);
                } else {
                    $from->send(json_encode(['type' => 'auth_failed']));
                    $from->close();
                }
            } else {
                $from->send(json_encode(['type' => 'auth_required']));
            }
            return;
        }

        // Handle various message types
        switch ($data['type'] ?? '') {
            case 'message':
                $this->handleMessage($from, $data);
                break;
            case 'fetch_history':
                $this->sendHistory($from, $data['withUserId'] ?? null);
                break;
            case 'fetch_conversations':
                $this->sendConversationsList($from);
                break;
            case 'mark_read':
                $this->markMessagesAsRead($from, $data['to'] ?? null);
                break;
            case 'typing':
                $this->broadcastTypingStatus($from, $data['to'] ?? null, true);
                break;
            case 'typing_stop':
                $this->broadcastTypingStatus($from, $data['to'] ?? null, false);
                break;
            case 'search_user_by_email':
                $this->searchUserByEmail($from, $data['email'] ?? '');
                break;
            case 'start_chat_with_user':
                $this->startChatWithUser($from, $data['userId'] ?? null);
                break;
            default:
                $from->send(json_encode(['type' => 'error', 'message' => 'Unknown command', 'data' => $data]));
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);

        if ($conn->authenticated && isset($this->userConnections[$conn->userId])) {
            // Broadcast offline status before removing the connection
            $this->broadcastUserStatus($conn->userId, 'offline');
            unset($this->userConnections[$conn->userId]);
        }
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        error_log("Error in chat service: " . $e->getMessage());
        $conn->close();
    }

    /**
     * Handles sending and storing a chat message.
     */
    private function handleMessage(ConnectionInterface $from, $data)
    {
        $senderId = $from->userId;
        $receiverId = $data['to'] ?? null;
        $messageText = $data['message'] ?? '';

        if (!$receiverId || !$messageText) {
            $from->send(json_encode(['type' => 'error', 'message' => 'Missing receiver or message']));
            return;
        }

        // Only general chat is supported now
        $room = $this->getGeneralChatRoom($senderId, $receiverId);

        if (!$room) {
            $from->send(json_encode(['type' => 'error', 'message' => 'Could not create chat room']));
            return;
        }

        $chatRoomId = $room['chat_room_id'];
        $now = date('Y-m-d H:i:s');

        // Store message
        $msgData = [
            'chat_room_id' => $chatRoomId,
            'sender_id' => $senderId,
            'receiver_id' => $receiverId,
            'message' => $messageText,
            'read_status' => 'sent',
            'created_at' => $now
        ];

        $messageId = $this->chatMessageModel->create($msgData);

        // Send to receiver if online
        if (isset($this->userConnections[$receiverId])) {
            $this->userConnections[$receiverId]->send(json_encode([
                'type' => 'message',
                'id' => $messageId,
                'from' => $senderId,
                'message' => $messageText,
                'created_at' => $now
            ]));

            // Update to delivered status
            $this->chatMessageModel->update(
                [
                    'delivered_at' => $now,
                    'read_status' => 'delivered'
                ],
                [
                    'filters' => [
                        'message_id' => $messageId
                    ]
                ]
            );
        }

        // Confirm to sender
        $from->send(json_encode([
            'type' => 'message_sent',
            'id' => $messageId,
            'to' => $receiverId,
            'message' => $messageText,
            'created_at' => $now
        ]));
    }

    /**
     * Gets or creates a general chat room between two users
     */
    private function getGeneralChatRoom($user1Id, $user2Id)
    {
        // Make sure user_1 is the smaller ID to maintain consistency
        $user1 = min($user1Id, $user2Id);
        $user2 = max($user1Id, $user2Id);

        // Find existing chat room
        $room = $this->chatRoomModel->readOne([
            'filters' => [
                'user_1' => $user1,
                'user_2' => $user2
            ]
        ]);

        // Create if doesn't exist
        if (!$room) {
            $roomId = $this->chatRoomModel->create([
                'user_1' => $user1,
                'user_2' => $user2
            ]);
            $room = $this->chatRoomModel->readOne([
                'filters' => [
                    'chat_room_id' => $roomId
                ]
            ]);
        }

        return $room;
    }

    /**
     * Sends chat history between the authenticated user and another user.
     */
    private function sendHistory(ConnectionInterface $conn, $withUserId)
    {
        $userId = $conn->userId;

        if (!$withUserId) {
            $conn->send(json_encode(['type' => 'error', 'message' => 'Missing user ID']));
            return;
        }

        // Only general chat is supported now
        $room = $this->chatRoomModel->readOne([
            'filters' => [
                'user_1' => min($userId, $withUserId),
                'user_2' => max($userId, $withUserId)
            ]
        ]);

        if (!$room) {
            $conn->send(json_encode([
                'type' => 'history',
                'withUserId' => $withUserId,
                'messages' => []
            ]));
            return;
        }

        $chatRoomId = $room['chat_room_id'];

        // Fetch messages in this room, ordered by created_at
        $messages = $this->chatMessageModel->read([
            'filters' => [
                'chat_room_id' => $chatRoomId
            ],
            'order' => 'created_at ASC'
        ]);

        $conn->send(json_encode([
            'type' => 'history',
            'withUserId' => $withUserId,
            'messages' => $messages ?: []
        ]));
    }

    /**
     * Sends the list of all conversations for the current user.
     */
    private function sendConversationsList(ConnectionInterface $conn)
    {
        $userId = $conn->userId;
        $conversations = [];

        // Find rooms where user is user_1
        $rooms1 = $this->chatRoomModel->read([
            'filters' => ['user_1' => $userId]
        ]);

        // Find rooms where user is user_2
        $rooms2 = $this->chatRoomModel->read([
            'filters' => ['user_2' => $userId]
        ]);

        // Merge the results
        $rooms = array_merge($rooms1 ?: [], $rooms2 ?: []);

        foreach ($rooms as $room) {
            // Determine the other user in the chat
            $otherUserId = ($room['user_1'] == $userId) ? $room['user_2'] : $room['user_1'];

            // Get user details
            $user = $this->userModel->readOne([
                'filters' => [
                    'user_id' => $otherUserId
                ]
            ]);

            if (!$user) continue;

            // Get last message
            $lastMessage = $this->chatMessageModel->read([
                'filters' => [
                    'chat_room_id' => $room['chat_room_id']
                ],
                'order' => 'created_at DESC',
                'limit' => 1
            ]);

            // Count unread messages
            $unreadCount = $this->chatMessageModel->count([
                'filters' => [
                    'chat_room_id' => $room['chat_room_id'],
                    'receiver_id' => $userId,
                    'read_status' => ['sent', 'delivered']
                ]
            ]);

            // Add to conversations list
            $conversations[] = [
                'userId' => $otherUserId,
                'name' => $user['name'] ?? 'User ' . $otherUserId,
                'avatar' => $user['profile_picture'] ?? '/assets/default-avatar.png',
                'lastMessage' => $lastMessage[0]['message'] ?? '',
                'lastMessageTime' => $lastMessage[0]['created_at'] ?? '',
                'unread' => $unreadCount,
                'status' => isset($this->userConnections[$otherUserId]) ? 'online' : 'offline',
                'chatRoomId' => $room['chat_room_id']
            ];
        }

        $conn->send(json_encode([
            'type' => 'conversations',
            'conversations' => $conversations
        ]));
    }

    /**
     * Mark messages from a specific user as read.
     */
    private function markMessagesAsRead(ConnectionInterface $conn, $fromUserId)
    {
        if (!$fromUserId) return;

        $userId = $conn->userId;

        // Only general chat is supported now
        $room = $this->chatRoomModel->readOne([
            'filters' => [
                'user_1' => min($userId, $fromUserId),
                'user_2' => max($userId, $fromUserId)
            ]
        ]);

        if (!$room) return;

        // Update all unread messages
        $this->chatMessageModel->update(
            [
                'read_status' => 'read'
            ],
            [
                'filters' => [
                    'chat_room_id' => $room['chat_room_id'],
                    'sender_id' => $fromUserId,
                    'receiver_id' => $userId,
                    'read_status' => ['sent', 'delivered']
                ]
            ]
        );

        // Notify the sender that messages have been read
        if (isset($this->userConnections[$fromUserId])) {
            $this->userConnections[$fromUserId]->send(json_encode([
                'type' => 'messages_read',
                'by' => $userId
            ]));
        }
    }

    /**
     * Broadcast user online/offline status to relevant users.
     */
    private function broadcastUserStatus($userId, $status)
    {
        // Find rooms where user is user_1
        $rooms1 = $this->chatRoomModel->read([
            'filters' => ['user_1' => $userId]
        ]);

        // Find rooms where user is user_2
        $rooms2 = $this->chatRoomModel->read([
            'filters' => ['user_2' => $userId]
        ]);

        // Merge the results
        $rooms = array_merge($rooms1 ?: [], $rooms2 ?: []);

        if (!$rooms) return;

        foreach ($rooms as $room) {
            // Get the other user
            $otherUserId = ($room['user_1'] == $userId) ? $room['user_2'] : $room['user_1'];

            // Send status update if they're online
            if (isset($this->userConnections[$otherUserId])) {
                $this->userConnections[$otherUserId]->send(json_encode([
                    'type' => 'user_status',
                    'userId' => $userId,
                    'status' => $status,
                    'chatRoomId' => $room['chat_room_id']
                ]));
            }
        }
    }

    /**
     * Broadcast typing status to the recipient.
     */
    private function broadcastTypingStatus(ConnectionInterface $from, $toUserId, $isTyping)
    {
        if (!$toUserId) return;

        $userId = $from->userId;

        if (isset($this->userConnections[$toUserId])) {
            $this->userConnections[$toUserId]->send(json_encode([
                'type' => $isTyping ? 'typing' : 'typing_stop',
                'userId' => $userId
            ]));
        }
    }

    /**
     * Search for a user by email and return minimal info if found.
     */
    private function searchUserByEmail(ConnectionInterface $conn, $email)
    {
        if (empty($email)) {
            $conn->send(json_encode([
                'type' => 'search_user_by_email_result',
                'success' => false,
                'message' => 'Email is required.'
            ]));
            return;
        }

        // Don't allow searching for yourself
        $userId = $conn->userId;
        $user = $this->userModel->readOne([
            'filters' => [
                'email' => $email
            ]
        ]);

        if (!$user || $user['user_id'] == $userId) {
            $conn->send(json_encode([
                'type' => 'search_user_by_email_result',
                'success' => false,
                'message' => 'User not found.'
            ]));
            return;
        }

        $conn->send(json_encode([
            'type' => 'search_user_by_email_result',
            'success' => true,
            'user' => [
                'userId' => $user['user_id'],
                'name' => $user['name'],
                'avatar' => $user['profile_picture'] ?? '/assets/default-avatar.png',
                'email' => $user['email'],
                'status' => isset($this->userConnections[$user['user_id']]) ? 'online' : 'offline'
            ]
        ]));
    }

    /**
     * Start a chat with a user by userId (creates room if not exists, returns conversation info).
     */
    private function startChatWithUser(ConnectionInterface $conn, $otherUserId)
    {
        $userId = $conn->userId;
        if (!$otherUserId || $otherUserId == $userId) {
            $conn->send(json_encode([
                'type' => 'start_chat_with_user_result',
                'success' => false,
                'message' => 'Invalid user.'
            ]));
            return;
        }

        // Check if user exists
        $user = $this->userModel->readOne([
            'filters' => [
                'user_id' => $otherUserId
            ]
        ]);
        if (!$user) {
            $conn->send(json_encode([
                'type' => 'start_chat_with_user_result',
                'success' => false,
                'message' => 'User not found.'
            ]));
            return;
        }

        // Get or create chat room
        $room = $this->getGeneralChatRoom($userId, $otherUserId);

        // Get last message
        $lastMessage = $this->chatMessageModel->read([
            'filters' => [
                'chat_room_id' => $room['chat_room_id']
            ],
            'order' => 'created_at DESC',
            'limit' => 1
        ]);

        // Count unread messages
        $unreadCount = $this->chatMessageModel->count([
            'filters' => [
                'chat_room_id' => $room['chat_room_id'],
                'receiver_id' => $userId,
                'read_status' => ['sent', 'delivered']
            ]
        ]);

        $conversation = [
            'userId' => $otherUserId,
            'name' => $user['name'] ?? 'User ' . $otherUserId,
            'avatar' => $user['profile_picture'] ?? '/assets/default-avatar.png',
            'lastMessage' => $lastMessage[0]['message'] ?? '',
            'lastMessageTime' => $lastMessage[0]['created_at'] ?? '',
            'unread' => $unreadCount,
            'status' => isset($this->userConnections[$otherUserId]) ? 'online' : 'offline',
            'chatRoomId' => $room['chat_room_id']
        ];

        $conn->send(json_encode([
            'type' => 'start_chat_with_user_result',
            'success' => true,
            'conversation' => $conversation
        ]));
    }
}