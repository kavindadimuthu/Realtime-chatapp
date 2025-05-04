<?php

require_once __DIR__ . '/../vendor/autoload.php';

use app\core\BaseController;
use app\core\BaseModel;
use app\core\Helpers\AuthHelper;

use app\models\Users\User;
use app\models\Users\UserSession;

/**
 * AuthController
 * Handles user registration, login, and logout.
 */
class AuthController extends BaseController
{
    protected $userModel;
    protected $sessionModel;
    protected $request;
    protected $response;

    public function __construct()
    {
        $this->userModel = new User;
        $this->sessionModel = new UserSession;
    }

    /**
     * Register a new user.
     * Expects JSON body: name, email, phone, password
     */
    public function register($request, $response)
    {
        error_log('Register method called'); // Debugging line
        $data = $request->getParsedBody();
        if(isset($data['firstName']) && isset($data['lastName'])){
            $data['name'] = $data['firstName'] . ' ' . $data['lastName'];
        } else {
            $data['name'] = $data['name'] ?? null;
        }

        error_log('Parsed body: ' . json_encode($data)); // Debugging line
        // Validate required fields
        if (
            empty($data['name']) ||
            empty($data['email']) ||
            empty($data['phone']) ||
            empty($data['password'])
        ) {
            return $response->sendError('All fields are required.', 422);
        }

        // Check if email already exists
        $existing = $this->userModel->readOne([
            'filters' => ['email' => $data['email']]
        ]);
        if ($existing) {
            return $response->sendError('Email already registered.', 409);
        }

        // Hash password
        $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);

        // Insert user
        $userId = $this->userModel->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'password' => $hashedPassword
        ]);

        if (!$userId) {
            return $response->sendError('Registration failed.', 500);
        }

        // Return success
        $user = $this->userModel->readOne(['filters' => ['user_id' => $userId]]);
        unset($user['password']);
        $response->setStatusCode(201);
        return $response->sendJson([
            'message' => 'Registration successful.',
            'user' => $user
        ]);
    }

    /**
     * Login a user.
     * Expects JSON body: email, password
     * Returns session token on success.
     */
    public function login($request, $response)
    {
        $data = $request->getParsedBody();
        error_log('Parsed body: ' . json_encode($data)); // Debugging line

        if (empty($data['email']) || empty($data['password'])) {
            return $response->sendError('Email and password required.', 422);
        }

        // Find user by email
        $user = $this->userModel->readOne([
            'filters' => ['email' => $data['email']]
        ]);
        if (!$user || !password_verify($data['password'], $user['password'])) {
            return $response->sendError('Invalid credentials.', 401);
        }
        
        // Generate session token
        $token = AuthHelper::generateSessionToken();
        $ip = $request->getClientIp();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        // Store session in DB
        $this->sessionModel->create([
            'user_id' => $user['user_id'],
            'session_token' => $token,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'is_active' => 1,
            'expires_at' => date('Y-m-d H:i:s', strtotime('+7 days'))
        ]);
        
        // Create session in server using authhelper
        $user['session_token'] = $token;
        $user['ip_address'] = $ip;
        AuthHelper::logIn($user);

        setcookie('session_token', $token, time() + 60 * 60 * 24 * 30, '/', '', false, false); // HttpOnly

        unset($user['password']);
        return $response->sendJson([
            'success' => true,
            'message' => 'Login successful.',
            'token' => $token,
            'user' => $user
        ]);
    }

    /**
     * Logout a user.
     * Accepts JSON body with optional parameter "all_devices" to logout from all devices
     * Invalidates the session token.
     */
    public function logout($request, $response)
    {
        error_log('Logout method called'); // Debugging line
        
        // Get token from cookie if not in header
        $token = null;
        $headers = $request->getHeaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;
        
        if ($authHeader && preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $token = $matches[1];
        } else {
            // Try to get token from cookie
            $token = $_COOKIE['session_token'] ?? null;
        }
        
        if (!$token) {
            return $response->sendError('Authorization token required.', 401);
        }
        
        // Parse request body to check if we should logout from all devices
        $data = $request->getParsedBody();
        $logoutAll = isset($data['all_devices']) && $data['all_devices'];
        
        // Find session
        $session = $this->sessionModel->readOne([
            'filters' => ['session_token' => $token, 'is_active' => 1]
        ]);
        
        if (!$session) {
            return $response->sendError('Invalid session.', 401);
        }
        
        // Make sure to destroy PHP session first to prevent redirect loops
        AuthHelper::logOut();
        session_unset();
        session_destroy();
        
        if ($logoutAll) {
            // Deactivate all sessions for this user
            $this->sessionModel->update(
                ['is_active' => 0],
                ['filters' => ['user_id' => $session['user_id']]]
            );
        } else {
            // Deactivate only current session
            $this->sessionModel->update(
                ['is_active' => 0],
                ['filters' => ['session_token' => $token]]
            );
        }
        
        // Clear all cookies that might be causing issues
        setcookie('session_token', '', time() - 3600, '/', '', false, false);
        setcookie('PHPSESSID', '', time() - 3600, '/', '', false, false);
        
        return $response->sendJson([
            'success' => true,
            'message' => $logoutAll ? 'Logged out from all devices.' : 'Logout successful.'
        ]);
    }
}