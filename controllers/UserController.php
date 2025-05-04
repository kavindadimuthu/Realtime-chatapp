<?php

require_once __DIR__ . '/../vendor/autoload.php';

use app\core\BaseController;
use \app\core\Helpers\AuthHelper;
use app\core\Utils\FileHandler;
use app\core\BaseModel;

class UserController extends BaseController
{
    /**
     * @var BaseModel $userModel
     */
    private $userModel;

    public function __construct()
    {
        $this->userModel = new BaseModel('user');
    }

    /**
     * Get the profile of a user by ID
     * If no ID is provided, returns the profile of the logged-in user
     * 
     * @param object $request
     * @param object $response
     * @return void
     */
    public function getProfile($request, $response)
    {
        error_log('Fetching user profile...');
        // Check if user is logged in
        if (!AuthHelper::isLoggedIn()) {
            return $response->sendError('Unauthorized access', 401);
        }
        
        // Get user ID from query params or use the logged-in user's ID
        $userId = $request->getParam('id') ?? AuthHelper::getCurrentUser()['user_id'];
        
        // Get user profile from database
        $user = $this->userModel->readOne([
            'columns' => ['user_id', 'name', 'email', 'phone', 'profile_picture', 'cover_picture', 'bio', 'created_at'],
            'filters' => ['user_id' => $userId]
        ]);
        
        if (!$user) {
            return $response->sendError('User not found', 404);
        }
        
        // Enhance profile picture and cover picture URLs if they exist
        if ($user['profile_picture']) {
            $user['profile_picture'] = $this->getFullImageUrl($user['profile_picture']);
        }
        
        if ($user['cover_picture']) {
            $user['cover_picture'] = $this->getFullImageUrl($user['cover_picture']);
        }
        
        return $response->sendJson([
            'success' => true,
            'data' => $user
        ]);
    }
    
    /**
     * Update the profile of the currently logged-in user
     * 
     * @param object $request
     * @param object $response
     * @return void
     */
    public function updateProfile($request, $response)
    {
        error_log('Updating user profile...');
        // Check if user is logged in
        if (!AuthHelper::isLoggedIn()) {
            return $response->sendError('Unauthorized access', 401);
        }
        
        $userId = AuthHelper::getCurrentUser()['user_id'];
        $userData = $request->getParsedBody();
        error_log('User data: ' . json_encode($userData));
        
        // Validate input data
        $validationErrors = $this->validateProfileData($userData);
        if (!empty($validationErrors)) {
            return $response->sendError('Validation failed', 400, ['errors' => $validationErrors]);
        }
        
        // Prepare update data (only allowed fields)
        $updateData = array_intersect_key($userData, array_flip(['name', 'phone', 'bio']));
        
        // Handle file uploads if they exist
        $files = $request->getFiles();
        error_log('Files: ' . json_encode($files));
        
        if (!empty($files['profile_picture'])) {
            $profilePicturePath = FileHandler::imageUploader(
                $files['profile_picture'], 
                'uploads/profile'
            );
            
            if ($profilePicturePath === false) {
                return $response->sendError('Failed to upload profile picture', 400);
            }
            
            $updateData['profile_picture'] = $profilePicturePath;
        }
        
        if (!empty($files['cover_picture'])) {
            $coverPicturePath = FileHandler::imageUploader(
                $files['cover_picture'], 
                'uploads/covers'
            );
            
            if ($coverPicturePath === false) {
                return $response->sendError('Failed to upload cover picture', 400);
            }
            
            $updateData['cover_picture'] = $coverPicturePath;
        }
        
        // Perform the update
        if (empty($updateData)) {
            return $response->sendError('No data provided for update', 400);
        }
        
        $success = $this->userModel->update($updateData, [
            'filters' => ['user_id' => $userId]
        ]);
        
        if (!$success) {
            return $response->sendError('Failed to update profile', 500);
        }
        
        // Get the updated user data
        $updatedUser = $this->userModel->readOne([
            'columns' => ['user_id', 'name', 'email', 'phone', 'profile_picture', 'cover_picture', 'bio', 'created_at'],
            'filters' => ['user_id' => $userId]
        ]);
        
        // Enhance image URLs
        if ($updatedUser['profile_picture']) {
            $updatedUser['profile_picture'] = $this->getFullImageUrl($updatedUser['profile_picture']);
        }
        
        if ($updatedUser['cover_picture']) {
            $updatedUser['cover_picture'] = $this->getFullImageUrl($updatedUser['cover_picture']);
        }
        
        return $response->sendJson([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => $updatedUser
        ]);
    }
    
    /**
     * Validate profile update data
     * 
     * @param array $data
     * @return array Validation errors
     */
    private function validateProfileData($data)
    {
        $errors = [];
        
        if (isset($data['name']) && (strlen($data['name']) < 2 || strlen($data['name']) > 50)) {
            $errors['name'] = 'Name must be between 2 and 50 characters';
        }
        
        if (isset($data['phone'])) {
            $phone = preg_replace('/[^0-9]/', '', $data['phone']);
            if (strlen($phone) < 10 || strlen($phone) > 12) {
                $errors['phone'] = 'Phone number must be between 10 and 12 digits';
            }
        }
        
        if (isset($data['bio']) && strlen($data['bio']) > 500) {
            $errors['bio'] = 'Bio cannot exceed 500 characters';
        }
        
        return $errors;
    }
    
    /**
     * Convert relative image paths to full URLs
     * 
     * @param string $imagePath
     * @return string
     */
    private function getFullImageUrl($imagePath)
    {
        if (strpos($imagePath, 'http') === 0) {
            return $imagePath; // Already a full URL
        }
        
        // If path already starts with a slash, don't add another one
        if (strpos($imagePath, '/') === 0) {
            return $imagePath;
        }
        
        return "/{$imagePath}";
    }
}