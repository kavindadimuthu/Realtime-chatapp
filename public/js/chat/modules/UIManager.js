import * as Utils from './Utils.js';

// Manages UI updates and interactions
export default class UIManager {
    constructor() {
        // DOM Elements
        this.sidebar = document.getElementById("sidebar");
        this.conversationList = document.getElementById("conversation-list");
        this.conversationsLoader = document.getElementById("conversations-loader");
        this.conversationCount = document.getElementById("conversation-count");
        this.contactSearch = document.getElementById("contact-search");
        this.chatInterface = document.getElementById("chat-interface");
        this.emptyState = document.getElementById("empty-state");
        this.chatMessages = document.getElementById("chat-messages");
        this.messagesLoader = document.getElementById("messages-loader");
        this.messageInput = document.getElementById("message-input");
        this.sendButton = document.getElementById("send-button");
        
        // User search elements
        this.userSearchContainer = document.getElementById("user-search-container");
        this.userEmailSearch = document.getElementById("user-email-search");
        this.searchResults = document.getElementById("search-results");
        
        // Chat user elements
        this.chatUserAvatar = document.getElementById("chat-user-avatar");
        this.chatUserName = document.getElementById("chat-user-name");
        this.chatUserStatus = document.getElementById("chat-user-status");
        this.chatUserStatusDot = document.querySelector(".chat-info .status .status-dot");
        this.chatUserProfileLink = document.getElementById("chat-user-profile-link");
        
        // Profile dropdown elements
        this.profileDropdownBtn = document.getElementById("profile-dropdown-btn");
        this.profileDropdown = document.getElementById("profile-dropdown");
        
        // Mobile elements
        this.mobileBackButton = document.getElementById("mobile-back-button");
        this.chatContainer = document.querySelector(".chat-container");
        
        // Fix for mobile viewport height
        this.setMobileViewportHeight();
        window.addEventListener("resize", () => this.setMobileViewportHeight());
        window.addEventListener("orientationchange", () => this.setMobileViewportHeight());
        
        // Setup auto-resize for message textarea
        this.setupAutoResizeTextarea();
    }
    
    // Fix for mobile viewport height
    setMobileViewportHeight() {
        // Get the window's inner height
        const vh = window.innerHeight * 0.01;
        // Set the value in the CSS custom property
        document.documentElement.style.setProperty("--vh", `${vh}px`);

        // Apply the height to key elements
        document.querySelector(".container").style.height = `calc(var(--vh, 1vh) * 100)`;

        if (window.innerWidth <= 768) {
            document.querySelector(".sidebar").style.height = `calc(var(--vh, 1vh) * 100)`;
            document.querySelector(".chat-container").style.height = `calc(var(--vh, 1vh) * 100)`;
            document.querySelector(".chat-interface").style.height = `calc(var(--vh, 1vh) * 100)`;
            document.querySelector(".chat-messages").style.maxHeight = `calc((var(--vh, 1vh) * 100) - 140px)`;
        }
    }

    // Update the container grid template columns when showing/hiding profile sidebar
    updateContainerGrid(isProfileVisible) {
        if (window.innerWidth > 768) {
            const container = document.querySelector('.container');
            if (isProfileVisible) {
                container.style.gridTemplateColumns = '300px 1fr 350px';
            } else {
                container.style.gridTemplateColumns = '300px 1fr 0';
            }
        }
    }
    
    // Auto-resize textarea
    setupAutoResizeTextarea() {
        this.messageInput.addEventListener("input", function() {
            this.style.height = "auto";
            this.style.height = this.scrollHeight + "px";
        });
    }
    
    // Show conversations loading state
    showConversationsLoading() {
        this.conversationsLoader.classList.remove("hidden");
    }
    
    // Hide conversations loading state
    hideConversationsLoading() {
        this.conversationsLoader.classList.add("hidden");
    }
    
    // Show messages loading state
    showMessagesLoading() {
        this.messagesLoader.classList.remove("hidden");
        this.chatMessages.innerHTML = "";
        this.chatMessages.appendChild(this.messagesLoader);
    }
    
    // Hide messages loading state
    hideMessagesLoading() {
        this.messagesLoader.classList.add("hidden");
    }
    
    // Create and display conversation items in sidebar
    displayConversations(conversations = []) {
        // Clear the list
        this.conversationList.innerHTML = "";

        // Update conversation count
        this.conversationCount.textContent = conversations.length;

        // No conversations state
        if (conversations.length === 0) {
            const emptyItem = document.createElement("div");
            emptyItem.className = "message-item";
            emptyItem.innerHTML = '<div class="message-info">No conversations yet</div>';
            this.conversationList.appendChild(emptyItem);
            return;
        }

        // Sort conversations by last message time (newest first)
        conversations.sort((a, b) => {
            return new Date(b.lastMessageTime) - new Date(a.lastMessageTime);
        });

        // Create conversation items
        conversations.forEach((conversation) => {
            const conversationItem = this.createConversationItem(conversation);
            this.conversationList.appendChild(conversationItem);
        });

        // Dispatch event indicating conversations have been loaded
        window.dispatchEvent(new Event("conversationsLoaded"));
    }
    
    // Create a single conversation list item
    createConversationItem(conversation) {
        const {
            userId, name, avatar, lastMessage, lastMessageTime, unread, status
        } = conversation;

        const item = document.createElement("div");
        item.className = "message-item";
        item.dataset.userId = userId;

        // Format time
        const timeFormatted = Utils.formatMessageTime(lastMessageTime);

        // Status indicator for online/offline
        const statusClass = status === "online" ? "status-indicator" : "hidden";

        // Create avatar HTML with consistent profile link format
        const avatarHtml = `
            <div class="avatar">
                <a href="/user/${userId}" class="profile-link" data-user-id="${userId}">
                    ${avatar && !avatar.includes("default-avatar.png")
                    ? `<img src="${avatar}" alt="Profile picture of ${name}" width="40" height="40">`
                    : `<div class="profile-text-placeholder" style="background-color: ${Utils.getColorFromName(name)}; 
                        display: flex; align-items: center; justify-content: center; width: 48px; height: 48px; margin-right: 12px;
                        border-radius: 50%; color: white; font-weight: bold;">${Utils.getInitials(name)}</div>`}
                    <div class="${statusClass}"></div>
                </a>
            </div>
        `;

        item.innerHTML = `
            ${avatarHtml}
            <div class="message-info">
                <div class="name-time">
                    <span class="name">${name}</span>
                    <span class="time">${timeFormatted}</span>
                </div>
                <div class="text">${lastMessage || "Start a conversation"}</div>
            </div>
            ${unread > 0 ? `<div class="unread-badge">${unread}</div>` : ""}
        `;

        return item;
    }
    
    // Update conversation in the list
    updateConversationPreview(userId, message, timestamp, unread = 0, moveToTop = true) {
        // Find the conversation item in the DOM
        const conversationItem = this.conversationList.querySelector(
            `.message-item[data-user-id="${userId}"]`
        );

        if (conversationItem) {
            // Update the item
            const messageText = conversationItem.querySelector(".text");
            const timeElement = conversationItem.querySelector(".time");
            messageText.textContent = message;
            timeElement.textContent = Utils.formatMessageTime(timestamp);

            // Update unread badge
            let unreadBadge = conversationItem.querySelector(".unread-badge");
            if (unread > 0) {
                if (!unreadBadge) {
                    unreadBadge = document.createElement("div");
                    unreadBadge.className = "unread-badge";
                    conversationItem.appendChild(unreadBadge);
                }
                unreadBadge.textContent = unread;
            } else if (unreadBadge) {
                unreadBadge.remove();
            }

            // Move the conversation to the top if needed
            if (moveToTop) {
                this.conversationList.insertBefore(
                    conversationItem,
                    this.conversationList.firstChild
                );
            }
            
            return true;
        }
        
        return false;
    }
    
    // Set active conversation in UI
    setActiveConversation(conversation) {
        // Remove active class from all conversation items
        const items = this.conversationList.querySelectorAll(".message-item");
        items.forEach((item) => item.classList.remove("active"));

        // Add active class to selected conversation
        const selectedItem = this.conversationList.querySelector(
            `.message-item[data-user-id="${conversation.userId}"]`
        );
        
        if (selectedItem) {
            selectedItem.classList.add("active");

            // Remove unread badge if exists
            const unreadBadge = selectedItem.querySelector(".unread-badge");
            if (unreadBadge) {
                unreadBadge.remove();
            }
        }

        // Show chat interface, hide empty state
        this.emptyState.classList.add("hidden");
        this.chatInterface.classList.remove("hidden");

        // Update chat header with user info
        this.updateChatHeader(conversation);

        // Show messages loading spinner
        this.showMessagesLoading();
        
        // Add mobile-specific behavior
        if (window.innerWidth <= 768) {
            this.chatContainer.classList.add("active");
            this.sidebar.classList.add("hidden");
        }
    }
    
    // Update chat header with user info
    updateChatHeader(conversation) {
        // Update avatar with consistent profile link
        const avatarHtml = conversation.avatar && !conversation.avatar.includes("default-avatar.png")
            ? `<img src="${conversation.avatar}" alt="Profile picture of ${conversation.name}" id="chat-user-avatar">`
            : `<div id="chat-user-avatar" class="profile-text-placeholder" 
                style="background-color: ${Utils.getColorFromName(conversation.name)}; display: flex; 
                align-items: center; justify-content: center; width: 48px; height: 48px; margin-right: 12px; 
                border-radius: 50%; color: white; font-weight: bold;">${Utils.getInitials(conversation.name)}</div>`;
        
        // Update the profile link with avatar
        this.chatUserProfileLink.innerHTML = avatarHtml;
        this.chatUserProfileLink.href = `/user/${conversation.userId}`;
        this.chatUserProfileLink.setAttribute("data-user-id", conversation.userId);
        
        // Keep reference to avatar element
        this.chatUserAvatar = document.getElementById("chat-user-avatar");

        // Update name
        this.chatUserName.textContent = conversation.name;

        // Update status indicator
        this.updateStatusDisplay(conversation.status);
    }
    
    // Update user status display in chat header
    updateStatusDisplay(status) {
        this.chatUserStatus.textContent = status === "online" ? "Active now" : "Offline";
        this.chatUserStatusDot.style.backgroundColor = status === "online" ? "#2ecc71" : "#ccc";
    }
    
    // Update user status in conversation list
    updateUserStatus(userId, status) {
        const conversationItem = this.conversationList.querySelector(
            `.message-item[data-user-id="${userId}"]`
        );
        
        if (conversationItem) {
            const statusIndicator = conversationItem.querySelector(".status-indicator");
            if (status === "online") {
                statusIndicator.classList.remove("hidden");
            } else {
                statusIndicator.classList.add("hidden");
            }
        }
    }
    
    // Display messages in the chat window
    displayMessages(messages, currentUserId) {
        // Clear the messages area
        this.chatMessages.innerHTML = "";

        if (!messages || messages.length === 0) {
            const emptyState = document.createElement("div");
            emptyState.className = "empty-state";
            emptyState.innerHTML = `
                <i class="far fa-comment"></i>
                <h3>No messages yet</h3>
                <p>Start the conversation by sending a message below.</p>
            `;
            this.chatMessages.appendChild(emptyState);
            return;
        }

        // Sort messages by time
        messages.sort((a, b) => {
            return new Date(a.created_at) - new Date(b.created_at);
        });

        // Group messages by date
        let currentDate = null;

        messages.forEach((message) => {
            // Check if we need to add a date divider
            const messageDate = new Date(message.created_at).toDateString();
            if (messageDate !== currentDate) {
                currentDate = messageDate;
                const dateDivider = document.createElement("div");
                dateDivider.className = "date-divider";
                dateDivider.innerHTML = `<span>${Utils.formatDateForDivider(
                    new Date(message.created_at)
                )}</span>`;
                this.chatMessages.appendChild(dateDivider);
            }

            // Add the message
            this.addMessageToChat(message, currentUserId, false);
        });

        // Scroll to the bottom of the chat
        this.chatMessages.scrollTop = this.chatMessages.scrollHeight;
    }
    
    // Add a single message to the chat
    addMessageToChat(message, currentUserId, isNew = false) {
        const isSent = message.sender_id == currentUserId;

        const messageEl = document.createElement("div");
        messageEl.className = `message ${isSent ? "sent" : "received"}`;
        messageEl.dataset.messageId = message.message_id || "";

        // Get active conversation data
        const activeConversation = {
            userId: isSent ? message.receiver_id : message.sender_id,
            name: document.getElementById("chat-user-name").textContent,
            avatar: isSent ? "" : this.chatUserAvatar.src || ""
        };

        // Update avatar HTML for received messages with consistent profile link format
        const avatarHtml = isSent
            ? ""
            : `
                <a href="/user/${activeConversation.userId}" class="profile-link" data-user-id="${activeConversation.userId}">
                    ${activeConversation.avatar && !activeConversation.avatar.includes("default-avatar.png")
                    ? `<img src="${activeConversation.avatar}" alt="Profile picture">`
                    : `<div class="profile-text-placeholder" style="background-color: ${Utils.getColorFromName(activeConversation.name)}; 
                        display: flex; align-items: center; justify-content: center; width: 36px; height: 36px; 
                        border-radius: 50%; color: white; font-weight: bold;">${Utils.getInitials(activeConversation.name)}</div>`}
                </a>`;

        // Format time
        const time = new Date(message.created_at);
        const timeFormatted = time.toLocaleTimeString([], {
            hour: "2-digit",
            minute: "2-digit",
        });

        // Add read/delivered status icon for sent messages
        const statusIcon = isSent
            ? message.read_status === "read"
                ? '<i class="fas fa-check-double" style="color: #2ecc71;"></i>'
                : message.read_status === "delivered"
                    ? '<i class="fas fa-check-double"></i>'
                    : '<i class="fas fa-check"></i>'
            : "";

        messageEl.innerHTML = `
            ${avatarHtml}
            <div class="message-content">
                <div class="text">${Utils.formatMessageText(message.message)}</div>
                <div class="time">${timeFormatted} ${statusIcon}</div>
            </div>
        `;

        // Add the message to the chat
        this.chatMessages.appendChild(messageEl);

        // If it's a new message, scroll to it
        if (isNew) {
            this.chatMessages.scrollTop = this.chatMessages.scrollHeight;
        }
    }
    
    // Update message status icon
    updateMessageStatus(messageId, status) {
        if (messageId) {
            const messageEl = this.chatMessages.querySelector(
                `.message[data-message-id="${messageId}"]`
            );
            if (messageEl) {
                this.updateMessageStatusIcon(messageEl, status);
            }
        } else {
            // Find the last sent message
            const sentMessages = this.chatMessages.querySelectorAll(".message.sent");
            if (sentMessages.length === 0) return;

            const lastMessage = sentMessages[sentMessages.length - 1];
            this.updateMessageStatusIcon(lastMessage, status);
        }
    }
    
    // Update all messages to read status
    updateAllMessagesStatus(status) {
        const sentMessages = this.chatMessages.querySelectorAll(".message.sent");
        sentMessages.forEach((message) => {
            this.updateMessageStatusIcon(message, status);
        });
    }
    
    // Update the status icon of a message
    updateMessageStatusIcon(messageEl, status) {
        const statusIcon = messageEl.querySelector(".time i");
        
        if (statusIcon) {
            switch (status) {
                case "sent":
                    statusIcon.className = "fas fa-check";
                    statusIcon.style.color = "";
                    break;
                case "delivered":
                    statusIcon.className = "fas fa-check-double";
                    statusIcon.style.color = "";
                    break;
                case "read":
                    statusIcon.className = "fas fa-check-double";
                    statusIcon.style.color = "#2ecc71";
                    break;
            }
        }
    }
    
    // Show typing indicator
    showTypingIndicator() {
        // Check if indicator already exists
        if (this.chatMessages.querySelector(".typing-indicator")) return;

        const typingIndicator = document.createElement("div");
        typingIndicator.className = "typing-indicator";
        typingIndicator.innerHTML = `
            <span></span>
            <span></span>
            <span></span>
        `;

        this.chatMessages.appendChild(typingIndicator);
        this.chatMessages.scrollTop = this.chatMessages.scrollHeight;
    }
    
    // Hide typing indicator
    hideTypingIndicator() {
        const indicator = this.chatMessages.querySelector(".typing-indicator");
        if (indicator) {
            indicator.remove();
        }
    }
    
    // Show user search interface
    showUserSearch() {
        this.userSearchContainer.classList.add("active");
        this.contactSearch.style.display = "none";
        document.querySelector(".all-messages").style.display = "none";
        this.userEmailSearch.focus();
        this.searchResults.innerHTML = "";
        
        // Additional behavior from chat.php
        const mainSidebarContent = document.getElementById("main-sidebar-content");
        const userSearchContainer = document.getElementById("user-search-container");
        mainSidebarContent.style.display = 'none';
        userSearchContainer.style.display = 'flex';
    }
    
    // Hide user search interface
    hideUserSearch() {
        this.userSearchContainer.classList.remove("active");
        this.contactSearch.style.display = "block";
        document.querySelector(".all-messages").style.display = "flex";
        this.userEmailSearch.value = "";
        this.searchResults.innerHTML = "";
        
        // Additional behavior from chat.php
        const mainSidebarContent = document.getElementById("main-sidebar-content");
        const userSearchContainer = document.getElementById("user-search-container");
        userSearchContainer.style.display = 'none';
        mainSidebarContent.style.display = 'flex';
    }
    
    // Show user search results
    displaySearchResults(userData = null, error = null) {
        this.searchResults.innerHTML = "";
        
        if (error) {
            this.searchResults.innerHTML = `<div class="search-status">${error}</div>`;
            return;
        }
        
        if (!userData) {
            this.searchResults.innerHTML = '<div class="search-status">No users found</div>';
            return;
        }
        
        // Create result item
        const resultItem = document.createElement("div");
        resultItem.className = "search-result-item";
        resultItem.innerHTML = `
            ${userData.avatar && !userData.avatar.includes("default-avatar.png")
            ? `<img src="${userData.avatar}" alt="Profile picture of ${userData.name}">`
            : `<div class="profile-text-placeholder" style="background-color: ${Utils.getColorFromName(userData.name)}; 
                display: flex; align-items: center; justify-content: center; width: 48px; height: 48px; margin-right: 12px;
                border-radius: 50%; color: white; font-weight: bold;">${Utils.getInitials(userData.name)}</div>`}
            <div class="user-info">
                <div class="user-name">${userData.name}</div>
                <div class="user-email">${userData.email}</div>
            </div>
            <div class="status-dot" style="background-color: ${userData.status === "online" ? "#2ecc71" : "#ccc"}"></div>
        `;
        
        this.searchResults.appendChild(resultItem);
        return resultItem;
    }
    
    // Show loading state in search results
    showSearchLoading() {
        this.searchResults.innerHTML =
            '<div class="search-status"><div class="spinner"></div>Searching...</div>';
    }
    
    // Show starting conversation loading state
    showStartChatLoading() {
        this.searchResults.innerHTML =
            '<div class="search-status"><div class="spinner"></div>Starting conversation...</div>';
    }
    
    // Toggle profile dropdown
    toggleProfileDropdown(event) {
        event.stopPropagation();
        this.profileDropdown.classList.toggle("active");
    }
    
    // Update user profile in header
    updateUserProfile(userData) {
        if (userData.avatar && !userData.avatar.includes("default-avatar.png")) {
            this.profileDropdownBtn.innerHTML = "";
            const img = document.createElement("img");
            img.src = userData.avatar;
            img.alt = "Your profile";
            img.className = "profile-image";
            this.profileDropdownBtn.appendChild(img);
        } else {
            // Create initials placeholder
            const name = userData.name || "User";
            const initials = Utils.getInitials(name);
            const hue = Utils.getColorFromName(name);

            this.profileDropdownBtn.innerHTML = "";
            this.profileDropdownBtn.style.backgroundColor = hue;
            this.profileDropdownBtn.style.color = "white";
            this.profileDropdownBtn.style.display = "flex";
            this.profileDropdownBtn.style.alignItems = "center";
            this.profileDropdownBtn.style.justifyContent = "center";
            this.profileDropdownBtn.style.fontWeight = "bold";
            this.profileDropdownBtn.textContent = initials;
        }
    }
    
    // Show error toast
    showErrorToast(message) {
        const toast = document.createElement("div");
        toast.className = "error-toast";
        toast.textContent = message;
        toast.style.position = "fixed";
        toast.style.bottom = "20px";
        toast.style.right = "20px";
        toast.style.padding = "10px 15px";
        toast.style.backgroundColor = "#e74c3c";
        toast.style.color = "white";
        toast.style.borderRadius = "5px";
        toast.style.zIndex = "1000";
        toast.style.boxShadow = "0 2px 10px rgba(0,0,0,0.2)";

        document.body.appendChild(toast);

        // Remove after 5 seconds
        setTimeout(() => {
            toast.style.opacity = "0";
            toast.style.transition = "opacity 0.5s ease";
            setTimeout(() => {
                document.body.removeChild(toast);
            }, 500);
        }, 5000);
    }
    
    // Show success toast
    showSuccessToast(message) {
        const toast = document.createElement("div");
        toast.className = "success-toast";
        toast.textContent = message;
        
        document.body.appendChild(toast);

        // Remove after 5 seconds
        setTimeout(() => {
            toast.style.opacity = "0";
            toast.style.transition = "opacity 0.5s ease";
            setTimeout(() => {
                document.body.removeChild(toast);
            }, 500);
        }, 5000);
    }
    
    // Show loading toast
    showLoadingToast(message) {
        const toast = document.createElement("div");
        toast.className = "loading-toast";
        toast.innerHTML = `
            <div class="spinner" style="display: inline-block; margin-right: 10px;"></div>
            <span>${message}</span>
        `;
        toast.style.position = "fixed";
        toast.style.top = "20px";
        toast.style.right = "20px";
        toast.style.padding = "10px 15px";
        toast.style.backgroundColor = "#333";
        toast.style.color = "white";
        toast.style.borderRadius = "5px";
        toast.style.zIndex = "1000";
        toast.style.boxShadow = "0 2px 10px rgba(0,0,0,0.2)";

        document.body.appendChild(toast);
        return toast;
    }
    
    // Play notification sound
    playNotificationSound() {
        // Create audio element if needed
        let audio = document.getElementById("notification-sound");
        if (!audio) {
            audio = document.createElement("audio");
            audio.id = "notification-sound";
            audio.src = "/assets/audio/notification-smooth-modern-stereo-332449.mp3";
            audio.style.display = "none";
            document.body.appendChild(audio);
        }

        audio.play().catch((e) => {
            console.log("Could not play notification sound:", e);
            // This often fails due to browser autoplay restrictions
        });
    }
    
    // Create overlay for mobile views
    createOverlay(id, closeCallback) {
        let overlay = document.getElementById(id);
        if (!overlay) {
            overlay = document.createElement("div");
            overlay.id = id;
            overlay.style.position = "fixed";
            overlay.style.top = "0";
            overlay.style.left = "0";
            overlay.style.width = "100%";
            overlay.style.height = "100%";
            overlay.style.backgroundColor = "rgba(0,0,0,0.5)";
            overlay.style.zIndex = "90";
            document.body.appendChild(overlay);

            overlay.addEventListener("click", () => {
                closeCallback();
                this.removeOverlay(id);
            });
        }
    }
    
    // Remove overlay
    removeOverlay(id) {
        const overlay = document.getElementById(id);
        if (overlay) {
            document.body.removeChild(overlay);
        }
    }
    
    // Get message input value and clear it
    getAndClearMessageInput() {
        const message = this.messageInput.value.trim();
        this.messageInput.value = "";
        this.messageInput.style.height = "auto";
        return message;
    }
}