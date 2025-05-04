// Manages WebSocket connections and messaging
export default class WebSocketManager {
    constructor(config) {
        this.socket = null;
        this.currentToken = config.token;
        this.reconnectAttempts = 0;
        this.messageHandlers = {};
        this.onConnect = config.onConnect || (() => {});
        this.onReconnect = config.onReconnect || (() => {});
        this.onDisconnect = config.onDisconnect || (() => {});
        this.onError = config.onError || (() => {});
    }

    // Connect to the WebSocket server
    connect() {
        const protocol = window.location.protocol === "https:" ? "wss:" : "ws:";
        const wsUrl = `${protocol}//${window.location.hostname}:8080`;
        // const wsUrl = `https://3bf3-45-121-88-169.ngrok-free.app`;

        this.socket = new WebSocket(wsUrl);

        this.socket.addEventListener("open", () => {
            console.log("WebSocket connection established");
            // Reset reconnect attempts on successful connection
            this.reconnectAttempts = 0;
            this.onConnect();
        });

        this.socket.addEventListener("message", (event) => this.handleMessage(event));

        this.socket.addEventListener("close", () => {
            console.log("WebSocket connection closed");
            this.onDisconnect();

            // Exponential backoff for reconnection
            const delay = Math.min(30000, Math.pow(2, this.reconnectAttempts) * 1000);
            this.reconnectAttempts++;

            console.log(`Attempting to reconnect in ${delay / 1000} seconds...`);
            setTimeout(() => {
                this.connect();
                this.onReconnect();
            }, delay);
        });

        this.socket.addEventListener("error", (error) => {
            console.error("WebSocket error:", error);
            this.onError(error);
        });
    }

    // Handle incoming messages
    handleMessage(event) {
        try {
            const data = JSON.parse(event.data);
            console.log("Received message:", data);

            // If there's a handler for this message type, call it
            if (this.messageHandlers[data.type]) {
                this.messageHandlers[data.type](data);
            }
        } catch (error) {
            console.error("Error handling message:", error);
        }
    }

    // Register message handler for a specific message type
    registerHandler(type, handler) {
        this.messageHandlers[type] = handler;
    }

    // Check if socket is connected and ready
    isReady() {
        return this.socket && this.socket.readyState === WebSocket.OPEN;
    }

    // Send message safely
    send(data) {
        if (this.isReady()) {
            this.socket.send(JSON.stringify(data));
            return true;
        } else {
            console.warn("Socket not ready. Message not sent:", data);
            return false;
        }
    }

    // Authenticate with the server
    authenticate() {
        if (this.currentToken) {
            this.send({
                type: "auth",
                token: this.currentToken
            });
        }
    }

    // Close the connection
    close() {
        if (this.socket && this.socket.readyState === WebSocket.OPEN) {
            this.socket.close();
        }
    }
}