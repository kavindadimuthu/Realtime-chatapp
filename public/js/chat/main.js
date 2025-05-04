import WebSocketManager from './modules/WebSocketManager.js';
import UIManager from './modules/UIManager.js';
import EventManager from './modules/EventManager.js';
import AuthManager from './modules/AuthManager.js';
import ConversationManager from './modules/ConversationManager.js';
import ProfileManager from './modules/ProfileManager.js';
import * as Utils from './modules/Utils.js';

/**
 * Main entry point for the chat application
 * Initializes and connects all modules for the chat functionality
 */
document.addEventListener("DOMContentLoaded", function () {
    // Unlock audio playback on first user gesture
    document.addEventListener("click", () => {
        let audio = document.getElementById("notification-sound");
        if (!audio) {
            audio = document.createElement("audio");
            audio.id = "notification-sound";
            audio.src = "/assets/audio/notification-smooth-modern-stereo-332449.mp3";
            audio.style.display = "none";
            document.body.appendChild(audio);
        }
        // Try to play and immediately pause to unlock
        audio.play().then(() => audio.pause()).catch(() => {});
    }, { once: true });

    // Initialize and connect modules
    initializeChat();

    // Function to initialize the chat app and connect all modules
    async function initializeChat() {
        // Create state object to share data between modules
        const state = {
            socket: null,
            conversations: [],
            messages: {},
            activeConversation: null,
            currentUserId: null,
            currentToken: null,
            reconnectAttempts: 0,
            typingTimeout: null,
            searchTimeout: null
        };

        // Initialize Auth Manager
        const authManager = new AuthManager();
        state.currentToken = authManager.init();

        // Redirect to login if not authenticated
        if (!state.currentToken) {
            authManager.redirectToLogin();
            return;
        }

        // Initialize UI Manager
        const uiManager = new UIManager();
        
        // Initialize WebSocket Manager with handlers
        const webSocketManager = new WebSocketManager({
            token: state.currentToken,
            onConnect: () => {
                // Authenticate after connection established
                // webSocketManager.authenticate();
            },
            onReconnect: () => {
                // Re-fetch data after reconnection if needed
                if (state.activeConversation) {
                    setTimeout(() => {
                        webSocketManager.send({
                            type: "fetch_history",
                            withUserId: state.activeConversation.userId
                        });
                    }, 1000);
                }
            }
        });

        // Initialize Conversation Manager
        const conversationManager = new ConversationManager();
        
        // Initialize Profile Manager
        const profileManager = new ProfileManager({
            ui: uiManager,
            websocket: webSocketManager,
            conversation: conversationManager
        });
        
        // Initialize Event Manager and connect all modules
        const eventManager = new EventManager({
            ui: uiManager,
            websocket: webSocketManager,
            conversation: conversationManager,
            auth: authManager,
            profile: profileManager
        });
        
        // Initialize all modules
        eventManager.init();
        
        // Connect to WebSocket server
        webSocketManager.connect();

        // Set mobile viewport height for better mobile experience
        setMobileViewportHeight();
        window.addEventListener("resize", setMobileViewportHeight);
        window.addEventListener("orientationchange", setMobileViewportHeight);
    }

    // Helper function to set correct viewport height on mobile
    function setMobileViewportHeight() {
        // First, get the viewport height and multiply it by 1% to get a value for a vh unit
        const vh = window.innerHeight * 0.01;
        // Then set the value in the --vh custom property to the root of the document
        document.documentElement.style.setProperty('--vh', `${vh}px`);
    }
});
