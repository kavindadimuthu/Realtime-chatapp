// Manages conversations and messages state
export default class ConversationManager {
    constructor() {
        this.conversations = []; // All conversations
        this.messages = {}; // Messages by conversation ID
        this.activeConversation = null; // Currently selected conversation
        this.currentUserId = null; // Logged in user ID
    }
    
    // Set current user ID
    setCurrentUserId(userId) {
        this.currentUserId = userId;
    }
    
    // Get current user ID
    getCurrentUserId() {
        return this.currentUserId;
    }
    
    // Set conversations data
    setConversations(conversations) {
        this.conversations = conversations;
        return this.conversations;
    }
    
    // Get all conversations
    getConversations() {
        return this.conversations;
    }
    
    // Get conversation by user ID
    getConversation(userId) {
        return this.conversations.find(c => c.userId == userId);
    }
    
    // Add or update a conversation
    updateConversation(conversation) {
        const index = this.conversations.findIndex(c => c.userId == conversation.userId);
        
        if (index !== -1) {
            // Update existing
            this.conversations[index] = {...this.conversations[index], ...conversation};
        } else {
            // Add new
            this.conversations.push(conversation);
        }
        
        return index !== -1 ? this.conversations[index] : conversation;
    }
    
    // Update conversation message preview
    updateConversationPreview(userId, message, timestamp) {
        const conversation = this.getConversation(userId);
        
        if (!conversation) return null;
        
        // Update conversation data
        conversation.lastMessage = message;
        conversation.lastMessageTime = timestamp;
        
        // If the message is from someone else and not the active conversation, increment unread
        if (userId != this.currentUserId && 
            (!this.activeConversation || this.activeConversation.userId != userId)) {
            conversation.unread = (conversation.unread || 0) + 1;
        }
        
        return conversation;
    }
    
    // Reset unread count for a conversation
    resetUnreadCount(userId) {
        const conversation = this.getConversation(userId);
        if (conversation) {
            conversation.unread = 0;
        }
        return conversation;
    }
    
    // Set active conversation
    setActiveConversation(conversation) {
        this.activeConversation = conversation;
        return this.activeConversation;
    }
    
    // Get active conversation
    getActiveConversation() {
        return this.activeConversation;
    }
    
    // Set messages for a conversation
    setMessages(userId, messages) {
        this.messages[userId] = messages;
        return this.messages[userId];
    }
    
    // Get messages for a conversation
    getMessages(userId) {
        return this.messages[userId] || [];
    }
    
    // Add a message to a conversation
    addMessage(userId, message) {
        if (!this.messages[userId]) {
            this.messages[userId] = [];
        }
        
        this.messages[userId].push(message);
        return message;
    }
    
    // Filter conversations by search term
    filterConversations(searchTerm) {
        if (!searchTerm) {
            return this.conversations;
        }
        
        return this.conversations.filter(conversation => {
            return conversation.name.toLowerCase().includes(searchTerm.toLowerCase());
        });
    }
    
    // Update user status
    updateUserStatus(userId, status) {
        const conversation = this.getConversation(userId);
        if (conversation) {
            conversation.status = status;
        }
        
        // If this is the active conversation, update it too
        if (this.activeConversation && this.activeConversation.userId == userId) {
            this.activeConversation.status = status;
        }
        
        return conversation;
    }
}