<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-status-bar-style" content="black-translucent">
    <title>TwinTalk - Chat</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/chat.css" />
</head>

<body>
    <div class="container">
        <!-- Chat Sidebar -->
        <div class="sidebar active" id="sidebar">
            <div class="sidebar-header">
                <a href="/" class="brand">TwinTalk</a>
                <div class="profile-dropdown-container">
                    <div id="profile-dropdown-btn" class="profile-image">
                        <!-- Will be replaced with actual image or initials dynamically -->
                        U
                    </div>
                    <div class="profile-dropdown" id="profile-dropdown">
                        <ul>
                            <li><a href="/profile"><i class="fas fa-user"></i> Profile</a></li>
                            <li><a href="#" id="logout-all-devices"><i class="fas fa-sign-out-alt"></i> Logout from all devices</a></li>
                            <li><a href="#" id="logout-current-device"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Main sidebar content (conversations) -->
            <div class="main-sidebar-content" id="main-sidebar-content">
                <div class="sidebar-top-actions">
                    <div class="search-container">
                        <input type="text" placeholder="Search conversations..." id="contact-search">
                    </div>
                    <button class="new-chat-btn" id="new-chat-btn" title="Start a new chat">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
                
                <div class="all-messages">
                    <span>Conversations</span>
                    <span class="message-count" id="conversation-count">0</span>
                </div>
                <div class="message-list" id="conversation-list">
                    <!-- Conversation list will be loaded dynamically -->
                    <div class="spinner" id="conversations-loader"></div>
                </div>
            </div>

            <!-- User search container -->
            <div class="user-search-container" id="user-search-container">
                <div class="sidebar-top-actions">
                    <button class="back-btn" id="cancel-search-btn">
                        <i class="fas fa-arrow-left"></i>
                    </button>
                    <input type="email" placeholder="Search new chat by email..." id="user-email-search" class="user-search-input">
                </div>
                <div class="search-results" id="search-results">
                    <!-- Search results will be displayed here -->
                </div>
            </div>
        </div>

        <!-- Main Chat Area -->
        <div class="chat-container">
            <!-- Empty State (shown when no chat is selected) -->
            <div class="empty-state" id="empty-state">
                <i class="far fa-comments"></i>
                <h3>Your Messages</h3>
                <p>Select a conversation or start a new one to chat with your collaborators.</p>
            </div>

            <!-- Chat Interface (hidden initially) -->
            <div class="chat-interface hidden" id="chat-interface">
                <div class="chat-header">
                    <div class="mobile-back-button" id="mobile-back-button">
                        <i class="fas fa-arrow-left"></i>
                    </div>
                    <a href="#" id="chat-user-profile-link" class="profile-link">
                        <!-- Avatar will be inserted here dynamically -->
                    </a>
                    <div class="chat-info">
                        <div class="name" id="chat-user-name"></div>
                        <div class="status"><span class="status-dot"></span> <span id="chat-user-status">Active now</span></div>
                    </div>
                </div>

                <div class="chat-messages" id="chat-messages">
                    <!-- Messages will be loaded dynamically -->
                    <div class="spinner" id="messages-loader"></div>
                </div>

                <div class="chat-input-container">
                    <div class="chat-input">
                        <div class="input-wrapper">
                            <textarea id="message-input" placeholder="Type a message..." rows="1"></textarea>
                        </div>
                        <div class="actions">
                            <button class="send-button" id="send-button" title="Send message">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- User Profile Sidebar (hidden by default) -->
        <div class="profile-sidebar hidden" id="profile-sidebar">
            <div class="profile-sidebar-header">
                <button class="close-profile-btn" id="close-profile-btn" title="Close profile">
                    <i class="fas fa-times"></i>
                </button>
                <h3>Profile</h3>
            </div>
            
            <div class="profile-content">
                <div class="profile-loading" id="profile-loading">
                    <div class="spinner"></div>
                    <p>Loading profile...</p>
                </div>
                
                <div class="profile-data" id="profile-data">
                    <!-- Cover image -->
                    <div class="profile-cover" id="profile-cover">
                        <div class="profile-cover-placeholder">
                            <i class="fas fa-image"></i>
                        </div>
                    </div>
                    
                    <!-- Profile basic info -->
                    <div class="profile-basic-info">
                        <div class="profile-avatar-large" id="profile-avatar-large">
                            <!-- Will be replaced dynamically -->
                        </div>
                        <h2 class="profile-name" id="profile-name">User Name</h2>
                    </div>
                    
                    <!-- Profile details -->
                    <div class="profile-details">
                        <div class="profile-detail-item">
                            <div class="detail-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="detail-content">
                                <div class="detail-label">Email</div>
                                <div class="detail-value" id="profile-email">user@example.com</div>
                            </div>
                        </div>
                        
                        <div class="profile-detail-item">
                            <div class="detail-icon">
                                <i class="fas fa-phone"></i>
                            </div>
                            <div class="detail-content">
                                <div class="detail-label">Phone</div>
                                <div class="detail-value" id="profile-phone">Not provided</div>
                            </div>
                        </div>
                        
                        <div class="profile-detail-item">
                            <div class="detail-icon">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            <div class="detail-content">
                                <div class="detail-label">Joined</div>
                                <div class="detail-value" id="profile-joined">January 1, 2023</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Bio section -->
                    <div class="profile-bio-section">
                        <h4>About</h4>
                        <p class="profile-bio" id="profile-bio">No bio provided.</p>
                    </div>
                    
                    <!-- Action button -->
                    <div class="profile-actions">
                        <button class="message-user-btn" id="message-user-btn">
                            <i class="fas fa-comment"></i> Message
                        </button>
                    </div>
                </div>
                
                <!-- Error state -->
                <div class="profile-error" id="profile-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <p>Could not load profile information.</p>
                    <button class="retry-btn" id="retry-profile-btn">Try Again</button>
                </div>
            </div>
        </div>
    </div>

    <script type="module" src="/js/chat/main.js"></script>
</body>

</html>