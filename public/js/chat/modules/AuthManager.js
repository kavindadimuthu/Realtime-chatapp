import * as Utils from './Utils.js';

// Handles authentication and session management
export default class AuthManager {
    constructor() {
        this.currentToken = null;
        this.currentUser = null;
    }
    
    // Initialize and get token from cookie
    init() {
        this.currentToken = Utils.getCookie("session_token");
        return this.currentToken;
    }
    
    // Check if user is authenticated
    isAuthenticated() {
        return !!this.currentToken;
    }
    
    // Get current token
    getToken() {
        return this.currentToken;
    }
    
    // Set current user data
    setCurrentUser(userData) {
        this.currentUser = userData;
    }
    
    // Get current user data
    getCurrentUser() {
        return this.currentUser;
    }
    
    // Handle logout from the app
    async logout(allDevices = false) {
        try {
            // Prepare the fetch options
            const options = {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Authorization: `Bearer ${this.currentToken}`,
                },
                body: JSON.stringify({ all_devices: allDevices }),
            };

            // Make the request to the logout endpoint
            const response = await fetch("/api/auth/logout", options);
            const data = await response.json();

            if (data.success || data.message) {
                // Clear cookies manually
                document.cookie = "session_token=; Path=/; Expires=Thu, 01 Jan 1970 00:00:01 GMT;";
                document.cookie = "PHPSESSID=; Path=/; Expires=Thu, 01 Jan 1970 00:00:01 GMT;";
                
                // Clear auth data
                this.currentToken = null;
                this.currentUser = null;
                
                return data;
            } else {
                throw data.error || "Logout failed";
            }
        } catch (error) {
            throw "Network error during logout";
        }
    }
    
    // Handle redirection to login page
    redirectToLogin() {
        window.location.href = "/login?t=" + new Date().getTime();
    }
}