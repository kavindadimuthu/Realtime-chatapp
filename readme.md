# TwinTalk - Real-time One-on-One Chat Application

TwinTalk is a real-time messaging application focused on providing a seamless one-on-one chat experience. Built with modern web technologies, it offers a clean interface and essential messaging features like typing indicators, read receipts, and online status indicators.

![TwinTalk Screenshot](/assets/images/app-landing-screenshot.png)

## Features

- **Real-time Messaging:** Instant message delivery with WebSocket technology
- **User Status:** See when your contacts are online/offline in real-time
- **Typing Indicators:** Know when someone is typing a response
- **Read Receipts:** Track when messages are delivered and read
- **User Profiles:** View and update user profiles with avatars and bios
- **User Search:** Find and connect with new users by email
- **Responsive Design:** Optimized for both desktop and mobile devices
- **Secure Authentication:** Session-based authentication system

## Technology Stack

- **Frontend:** HTML, CSS, JavaScript (Vanilla JS modules)
- **Backend:** PHP
- **WebSockets:** Ratchet PHP WebSocket server
- **Database:** MySQL
- **Authentication:** JWT Tokens

## Project Structure

```
TwinTalk/
├── core/                  # Core application classes
├── controllers/           # Application controllers
├── models/                # Database models
│   ├── Communication/     # Chat models
│   └── Users/             # User models
├── public/                # Public assets and entry points
│   ├── assets/            # Images, icons and audio files
│   ├── css/               # Stylesheets
│   ├── js/                # JavaScript files
│   │   └── chat/          # Chat modules
│   │       └── modules/   # JS module files
│   └── index.php          # Main entry point
├── services/              # Service classes
│   └── ChatService.php    # WebSocket service
├── migrations/            # Database schema files
├── vendor/                # Composer dependencies
└── views/                 # Template files
    └── pages/             # Page templates
```

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Composer
- Web server (Apache/Nginx)
- Node.js (for additional tools, optional)

## Local Setup

Follow these steps to get TwinTalk running on your local machine:

### 1. Clone the repository

```bash
git clone https://github.com/yourusername/twintalk.git
cd twintalk
```

### 2. Install dependencies

```bash
composer install
```

### 3. Database configuration

1. Create a MySQL database for the application
2. Import the database schema from migrations/schema.sql
3. Copy .env.example to .env and update with your database credentials:

```bash
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=twintalk
DB_USERNAME=root
DB_PASSWORD=yourpassword
```

### 4. Configure web server

#### For Apache:
Ensure the document root points to the public directory.

Example Apache virtual host:
```
<VirtualHost *:80>
    ServerName twintalk.local
    DocumentRoot "/path/to/twintalk/public"
    
    <Directory "/path/to/twintalk/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

#### For Nginx:
```
server {
    listen 80;
    server_name twintalk.local;
    root /path/to/twintalk/public;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
        index index.php;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### 5. Start WebSocket server

The WebSocket server must be running for real-time functionality:
```bash
php chat-server.php
```

For production environments, consider using a process manager like Supervisor to keep the WebSocket server running.

### 6. Access the application

1. Add the following to your hosts file:
```
127.0.0.1 twintalk.local
```

2. Access the application at http://twintalk.local

## Usage Guide

### Registration and Login
- Visit the landing page and click "Log In"
- For new users, click the "Register" tab
- Fill in your details and create an account
- Log in with your credentials

### Starting a Conversation
- Click the "+" button in the sidebar
- Search for a user by email address
- Click on the search result to start a chat

### Managing Your Profile
- Click your profile icon in the top-right corner
- Select "Profile" from the dropdown menu
- View your profile details
- Click "Edit" to update your information

### Sending Messages
- Select a conversation from the sidebar
- Type your message in the input field at the bottom
- Press Enter or click the send button

### Logging Out
- Click your profile icon in the top-right corner
- Select "Logout" to log out from the current device
- Select "Logout from all devices" to terminate all active sessions

## Development

### Key JavaScript Modules
- WebSocketManager.js: Handles WebSocket connections and messaging
- UIManager.js: Manages UI updates and interactions
- EventManager.js: Sets up event listeners and communication between components
- ConversationManager.js: Manages conversations and messages state
- ProfileManager.js: Handles user profile functionality
- AuthManager.js: Manages authentication and session handling

### Adding New Features
When adding new features:

1. Identify which module should contain the functionality
2. Update relevant back-end services if needed
3. Add appropriate UI elements
4. Wire up event handlers in EventManager.js

## License

MIT License

## Contact

For questions or support, please contact your-email@example.com