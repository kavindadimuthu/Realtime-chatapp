import * as Utils from './Utils.js';

// Manages all event listeners and communication between components
export default class EventManager {
    constructor(config) {
        this.ui = config.ui;
        this.websocket = config.websocket;
        this.conversation = config.conversation;
        this.auth = config.auth;
        this.profile = config.profile;
        
        // DOM elements for event handling
        this.sendButton = document.getElementById("send-button");
        this.messageInput = document.getElementById("message-input");
        this.contactSearch = document.getElementById("contact-search");
        this.newChatBtn = document.getElementById("new-chat-btn");
        this.cancelSearchBtn = document.getElementById("cancel-search-btn");
        this.userEmailSearch = document.getElementById("user-email-search");
        this.profileDropdownBtn = document.getElementById("profile-dropdown-btn");
        this.profileDropdown = document.getElementById("profile-dropdown");
        this.logoutCurrentDevice = document.getElementById("logout-current-device");
        this.logoutAllDevices = document.getElementById("logout-all-devices");
        this.mobileBackButton = document.getElementById("mobile-back-button");
        this.sidebar = document.getElementById("sidebar");
        this.chatContainer = document.querySelector(".chat-container");
        this.mainSidebarContent = document.getElementById("main-sidebar-content");
        this.userSearchContainer = document.getElementById("user-search-container");
        
        // Tracking variables
        this.typingTimeout = null;
        this.searchTimeout = null;
    }
    
    // Initialize all event listeners
    init() {
        this.setupWebSocketHandlers();
        this.setupMessageHandlers();
        this.setupUIHandlers();
        this.setupMobileHandlers();
        this.setupTypingHandlers();
        this.setupSearchHandlers();
        this.setupProfileHandlers();
        
        // Initialize profile manager
        if (this.profile) {
            this.profile.init();
        }
    }
    
    // Setup WebSocket message handlers
    setupWebSocketHandlers() {
        // Auth handlers
        this.websocket.registerHandler("auth_success", (data) => {
            this.conversation.setCurrentUserId(data.userId);
            
            // Update profile UI with user data
            this.ui.updateUserProfile({
                name: data.userName,
                avatar: data.userAvatar
            });
            
            // Store user data
            this.auth.setCurrentUser({
                userId: data.userId,
                name: data.userName,
                avatar: data.userAvatar
            });
            
            // Fetch conversations once authenticated
            this.websocket.send({
                type: "fetch_conversations"
            });
        });
        
        this.websocket.registerHandler("auth_failed", () => {
            console.error("Authentication failed");
            this.auth.redirectToLogin();
        });
        
        this.websocket.registerHandler("auth_required", () => {
            const token = this.auth.getToken();
            if (token) {
                this.websocket.send({
                    type: "auth",
                    token: token
                });
            } else {
                this.auth.redirectToLogin();
            }
        });
        
        // Conversation handlers
        this.websocket.registerHandler("conversations", (data) => {
            const conversations = this.conversation.setConversations(data.conversations);
            this.ui.displayConversations(conversations);
            this.ui.hideConversationsLoading();
        });
        
        // Message handlers
        this.websocket.registerHandler("message", (data) => {
            const { from, id, message, created_at } = data;
            
            // Add message to conversation if it's the active one
            const activeConv = this.conversation.getActiveConversation();
            if (activeConv && activeConv.userId == from) {
                // Add to UI
                this.ui.addMessageToChat(
                    {
                        message_id: id,
                        sender_id: from,
                        message: message,
                        read_status: "delivered",
                        created_at: created_at
                    },
                    this.conversation.getCurrentUserId(),
                    true
                );
                
                // Add to state
                this.conversation.addMessage(from, {
                    message_id: id,
                    sender_id: from,
                    message: message,
                    read_status: "delivered",
                    created_at: created_at
                });
                
                // Mark as read
                this.websocket.send({
                    type: "mark_read",
                    to: from
                });
            }
            
            // Update conversation preview
            const updatedConv = this.conversation.updateConversationPreview(from, message, created_at);
            if (updatedConv) {
                this.ui.updateConversationPreview(
                    from, 
                    message, 
                    created_at, 
                    updatedConv.unread, 
                    true
                );
            }
            
            console.log("Message received before sound:", data);
            // Play notification if not active conversation
            if (!activeConv || activeConv.userId != from) {
                console.log("Playing notification sound for new message");
                this.ui.playNotificationSound();
            }
        });
        
        this.websocket.registerHandler("history", (data) => {
            if (data.messages && data.withUserId) {
                const userId = data.withUserId;
                
                // Store messages
                this.conversation.setMessages(userId, data.messages);
                
                // If this is the active conversation, display messages
                const activeConv = this.conversation.getActiveConversation();
                if (activeConv && activeConv.userId == userId) {
                    this.ui.displayMessages(data.messages, this.conversation.getCurrentUserId());
                }
            }
            
            this.ui.hideMessagesLoading();
        });
        
        this.websocket.registerHandler("message_sent", (data) => {
            const activeConv = this.conversation.getActiveConversation();
            if (activeConv && activeConv.userId == data.to) {
                this.ui.updateMessageStatus(data.id, "sent");
            }
        });
        
        this.websocket.registerHandler("messages_read", (data) => {
            const activeConv = this.conversation.getActiveConversation();
            if (data.by && activeConv && activeConv.userId == data.by) {
                this.ui.updateAllMessagesStatus("read");
            }
        });
        
        this.websocket.registerHandler("user_status", (data) => {
            // Update in conversation data
            this.conversation.updateUserStatus(data.userId, data.status);
            
            // Update in UI
            this.ui.updateUserStatus(data.userId, data.status);
            
            // If active conversation, update header
            const activeConv = this.conversation.getActiveConversation();
            if (activeConv && activeConv.userId == data.userId) {
                this.ui.updateStatusDisplay(data.status);
            }
        });
        
        // Typing handlers
        this.websocket.registerHandler("typing", (data) => {
            const activeConv = this.conversation.getActiveConversation();
            if (activeConv && activeConv.userId == data.userId) {
                this.ui.showTypingIndicator();
            }
        });
        
        this.websocket.registerHandler("typing_stop", (data) => {
            const activeConv = this.conversation.getActiveConversation();
            if (activeConv && activeConv.userId == data.userId) {
                this.ui.hideTypingIndicator();
            }
        });
        
        // User search handlers
        this.websocket.registerHandler("search_user_by_email_result", (data) => {
            this.handleUserSearchResult(data);
        });
        
        this.websocket.registerHandler("start_chat_with_user_result", (data) => {
            this.handleStartChatResult(data);
        });
        
        // Error handler
        this.websocket.registerHandler("error", (data) => {
            console.error("Socket error:", data.message);
            this.ui.showErrorToast(data.message);
        });
    }
    
    // Handle user search results
    handleUserSearchResult(data) {
        if (!data.success) {
            this.ui.displaySearchResults(null, data.message || "No users found");
            return;
        }
        
        const resultItem = this.ui.displaySearchResults(data.user);
        
        // Add click handler to start chat
        if (resultItem) {
            resultItem.addEventListener("click", () => {
                this.startChatWithUser(data.user.userId);
            });
        }
    }
    
    // Start chat with user
    startChatWithUser(userId) {
        if (!userId || !this.websocket.isReady()) return;
        
        // Show loading state
        this.ui.showStartChatLoading();
        
        // Send request to start chat
        this.websocket.send({
            type: "start_chat_with_user",
            userId: userId
        });
    }
    
    // Handle start chat result
    handleStartChatResult(data) {
        if (!data.success) {
            this.ui.displaySearchResults(null, data.message || "Could not start conversation");
            return;
        }
        
        // Add new conversation to conversations list
        const newConversation = data.conversation;
        
        // Check if conversation already exists
        const existingConv = this.conversation.getConversation(newConversation.userId);
        
        if (!existingConv) {
            // Add to conversations data
            this.conversation.updateConversation(newConversation);
            
            // Update UI
            this.ui.displayConversations(this.conversation.getConversations());
        }
        
        // Hide search panel
        this.hideUserSearch();
        
        // Open the conversation
        this.openConversation(newConversation);
    }
    
    // Setup message sending and input handlers
    setupMessageHandlers() {
        // Send message on button click
        this.sendButton.addEventListener("click", () => this.sendMessage());
        
        // Send message on Enter (but new line on Shift+Enter)
        this.messageInput.addEventListener("keydown", (event) => {
            if (event.key === "Enter" && !event.shiftKey) {
                event.preventDefault();
                this.sendMessage();
            }
        });
    }
    
    // Send a message
    sendMessage() {
        const activeConv = this.conversation.getActiveConversation();
        if (!activeConv || !this.websocket.isReady()) return;
        
        const message = this.ui.getAndClearMessageInput();
        if (message === "") return;
        
        // Censor contact information
        const censoredMessage = Utils.censorContactInfo(message);
        
        // Send to server
        this.websocket.send({
            type: "message",
            to: activeConv.userId,
            message: censoredMessage
        });
        
        // Add to UI (optimistic update)
        const now = new Date();
        this.ui.addMessageToChat(
            {
                sender_id: this.conversation.getCurrentUserId(),
                message: censoredMessage,
                created_at: now.toISOString(),
                read_status: "sent"
            },
            this.conversation.getCurrentUserId(),
            true
        );
        
        // Add to conversation state
        this.conversation.addMessage(activeConv.userId, {
            sender_id: this.conversation.getCurrentUserId(),
            message: censoredMessage,
            created_at: now.toISOString(),
            read_status: "sent"
        });
        
        // Update conversation preview
        const updatedConv = this.conversation.updateConversationPreview(
            activeConv.userId,
            censoredMessage,
            now.toISOString()
        );
        
        if (updatedConv) {
            this.ui.updateConversationPreview(
                activeConv.userId,
                censoredMessage,
                now.toISOString(),
                0,
                true
            );
        }
        
        // Clear typing timeout and send typing_stop
        if (this.typingTimeout) {
            clearTimeout(this.typingTimeout);
            this.websocket.send({
                type: "typing_stop",
                to: activeConv.userId
            });
        }
    }
    
    // Open a conversation
    openConversation(conversation) {
        // Update state
        this.conversation.setActiveConversation(conversation);
        this.conversation.resetUnreadCount(conversation.userId);
        
        // Update UI
        this.ui.setActiveConversation(conversation);
        
        // Request chat history
        this.websocket.send({
            type: "fetch_history",
            withUserId: conversation.userId
        });
        
        // Mark messages as read
        this.websocket.send({
            type: "mark_read",
            to: conversation.userId
        });
        
        // Hide typing indicator
        this.ui.hideTypingIndicator();
        
        // If we already have messages for this conversation, display them
        const existingMessages = this.conversation.getMessages(conversation.userId);
        if (existingMessages && existingMessages.length > 0) {
            setTimeout(() => {
                this.ui.displayMessages(existingMessages, this.conversation.getCurrentUserId());
            }, 300);
        }
    }
    
    // Setup main UI interaction handlers
    setupUIHandlers() {
        // Conversation search
        this.contactSearch.addEventListener("input", (e) => {
            const searchTerm = e.target.value.toLowerCase();
            const filtered = this.conversation.filterConversations(searchTerm);
            this.ui.displayConversations(filtered);
        });
        
        // New Chat button
        this.newChatBtn.addEventListener("click", () => {
            this.showUserSearch();
        });
        
        // Cancel search button
        this.cancelSearchBtn.addEventListener("click", () => {
            this.hideUserSearch();
        });
        
        // Setup conversation item click handlers
        window.addEventListener("conversationsLoaded", () => {
            const items = document.querySelectorAll(".message-item");
            items.forEach(item => {
                if (item.dataset.userId) {
                    item.addEventListener("click", () => {
                        const userId = item.dataset.userId;
                        const conv = this.conversation.getConversation(userId);
                        if (conv) {
                            this.openConversation(conv);
                        }
                    });
                }
            });
        });
    }
    
    // Show user search interface
    showUserSearch() {
        this.userSearchContainer.style.display = 'flex';
        this.mainSidebarContent.style.display = 'none';
        this.userEmailSearch.focus();
    }
    
    // Hide user search interface
    hideUserSearch() {
        this.userSearchContainer.style.display = 'none';
        this.mainSidebarContent.style.display = 'flex';
    }
    
    // Setup mobile-specific handlers
    setupMobileHandlers() {
        if (this.mobileBackButton) {
            this.mobileBackButton.addEventListener("click", () => {
                if (window.innerWidth <= 768) {
                    this.chatContainer.classList.remove("active");
                    this.sidebar.classList.remove("hidden");
                    this.ui.removeOverlay("chat-overlay");
                }
            });
        }
        
        // Add resize event listener to handle mobile/desktop transitions
        window.addEventListener("resize", () => {
            // Show/hide mobile back button based on screen size
            if (this.mobileBackButton) {
                if (window.innerWidth <= 768) {
                    this.mobileBackButton.style.display = "flex";
                } else {
                    this.mobileBackButton.style.display = "none";
                }
            }
        });
    }
    
    // Setup typing indicator handlers
    setupTypingHandlers() {
        this.messageInput.addEventListener("input", () => {
            const activeConv = this.conversation.getActiveConversation();
            if (!activeConv || !this.websocket.isReady()) return;
            
            // Send typing status
            this.websocket.send({
                type: "typing",
                to: activeConv.userId
            });
            
            // Clear existing timeout if any
            if (this.typingTimeout) {
                clearTimeout(this.typingTimeout);
            }
            
            // Set timeout to send typing_stop after inactivity
            this.typingTimeout = setTimeout(() => {
                this.websocket.send({
                    type: "typing_stop",
                    to: activeConv.userId
                });
            }, 3000);
        });
    }
    
    // Setup search handlers
    setupSearchHandlers() {
        this.userEmailSearch.addEventListener("input", (e) => {
            const email = e.target.value.trim();
            
            // Clear previous timeout
            if (this.searchTimeout) {
                clearTimeout(this.searchTimeout);
            }
            
            // Search after 500ms of user stopping typing
            this.searchTimeout = setTimeout(() => {
                if (email.length >= 3) {
                    this.searchUserByEmail(email);
                } else if (email.length === 0) {
                    // Clear results if search field is empty
                    this.ui.searchResults.innerHTML = "";
                } else {
                    // Show message for short queries
                    this.ui.searchResults.innerHTML = 
                        '<div class="search-status">Please enter at least 3 characters</div>';
                }
            }, 500);
        });
    }
    
    // Search users by email
    searchUserByEmail(email) {
        if (!email || !this.websocket.isReady()) return;
        
        // Show loading state
        this.ui.showSearchLoading();
        
        // Send search request
        this.websocket.send({
            type: "search_user_by_email",
            email: email
        });
    }
    
    // Setup profile dropdown handlers
    setupProfileHandlers() {
        // Toggle profile dropdown
        this.profileDropdownBtn.addEventListener("click", (e) => {
            console.log("Profile dropdown clicked");
            this.ui.playNotificationSound();
            this.ui.toggleProfileDropdown(e);
        });
        
        // Close dropdown when clicking elsewhere
        document.addEventListener("click", (event) => {
            if (
                !this.profileDropdownBtn.contains(event.target) && 
                !this.profileDropdown.contains(event.target)
            ) {
                this.profileDropdown.classList.remove("active");
            }
        });
        
        // Add event listener for profile link
        const profileLink = document.querySelector('#profile-dropdown a[href="/profile"]');
        if (profileLink) {
            profileLink.addEventListener('click', (e) => {
                e.preventDefault();
                
                // Hide dropdown
                this.profileDropdown.classList.remove("active");
                
                // Show self profile
                this.profile.showSelfProfile();
            });
        }
        
        // Logout handlers
        this.logoutCurrentDevice.addEventListener("click", (e) => {
            e.preventDefault();
            this.handleLogout(false);
        });
        
        this.logoutAllDevices.addEventListener("click", (e) => {
            e.preventDefault();
            this.handleLogout(true);
        });
    }
    
    // Handle logout process
    handleLogout(allDevices) {
        // Show loading indicator
        const loadingToast = this.ui.showLoadingToast("Logging out...");
        
        this.auth.logout(allDevices)
            .then(() => {
                // Remove loading toast
                if (loadingToast) {
                    document.body.removeChild(loadingToast);
                }
                
                // Force disconnect WebSocket
                this.websocket.close();
                
                // Redirect to login page
                this.auth.redirectToLogin();
            })
            .catch((error) => {
                // Remove loading toast
                if (loadingToast) {
                    document.body.removeChild(loadingToast);
                }
                
                console.error("Logout error:", error);
                this.ui.showErrorToast("Logout failed. Please try again.");
            });
    }
}