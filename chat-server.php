<?php
require __DIR__ . '/vendor/autoload.php';

use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use Dotenv\Dotenv;
use app\services\ChatService; // Add this line

// Load .env
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Start server
$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new ChatService() // Change Chat() to ChatService()
        )
    ),
    8080
);

echo "BrandBoost Chat WebSocket server running on port 8080\n";
$server->run();
