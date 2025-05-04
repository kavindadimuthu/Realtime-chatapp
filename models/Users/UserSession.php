<?php

namespace app\models\Users;

use app\core\BaseModel;

class UserSession extends BaseModel
{
    protected $table = 'user_sessions';

    /**
     * Create a new user session.
     * @param array $data
     * @return bool
     */
    public function createSession(array $data)
    {
        return $this->create($data);
    }

    /**
     * Get session by session token.
     * @param string $token
     * @return array|false
     */
    public function getSessionByToken(string $token)
    {
        return $this->readOne(['session_token' => $token]);
    }

    /**
     * Get all active sessions for a user.
     * @param int $userId
     * @return array|false
     */
    public function getActiveSessionsByUserId(int $userId)
    {
        return $this->read([
            'user_id' => $userId,
            'is_active' => 1
        ]);
    }

    /**
     * Update session by token.
     * @param string $token
     * @param array $data
     * @return bool
     */
    public function updateSessionByToken(string $token, array $data)
    {
        return $this->update(['session_token' => $token], $data);
    }

    /**
     * Deactivate (logout) a session by token.
     * @param string $token
     * @return bool
     */
    public function deactivateSession(string $token)
    {
        return $this->update(['session_token' => $token], ['is_active' => 0]);
    }

    /**
     * Delete expired sessions.
     * @return bool
     */
    public function deleteExpiredSessions()
    {
        return $this->delete([
            'expires_at <=' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Delete all sessions for a user (logout everywhere).
     * @param int $userId
     * @return bool
     */
    public function deleteAllSessionsByUserId(int $userId)
    {
        return $this->delete(['user_id' => $userId]);
    }
}