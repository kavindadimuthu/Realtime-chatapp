<?php

require_once __DIR__ . '/../vendor/autoload.php';

use app\core\Application as Application;
use app\core\Helpers\SessionHelper;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Start the session
SessionHelper::startSession();

error_log('Session started'); // Debugging line
error_log('Application instance created');

// Create the application instance
$app = new Application();

// =====================================================================================================================================================================
// ====================================================             View routes            ==============================================================================
// =====================================================================================================================================================================

$app->router->get('/', 'ViewController@home'); // Home (Landing Page)
$app->router->get('/login', 'ViewController@login'); // Login Page
$app->router->get('/chat', 'ViewController@chat'); // Chat Page

$app->router->get('/404', 'ViewController@error404'); // 404 Error Page


// =====================================================================================================================================================================
// ====================================================             API routes            ==============================================================================
// =====================================================================================================================================================================

// ==================================
// Authentication Routes
// ==================================
$app->router->post('/api/auth/register', 'AuthController@register'); // Register/Create User
$app->router->post('/api/auth/login', 'AuthController@login'); // Login
$app->router->post('/api/auth/logout', 'AuthController@logout'); // Logout

// ==================================
// User Management APIs
// ==================================
$app->router->get('/api/user/profile', 'UserController@getProfile'); // Get User Profile
$app->router->post('/api/user/profile', 'UserController@updateProfile'); // Update User Profile


// Run the application
$app->run();