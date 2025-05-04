<?php

namespace app\models\Users;

use app\core\BaseModel;

class User extends BaseModel
{
    protected $table = 'user';

    /**
     * Retrieves a user by their ID.
     *
     * @param int $id The ID of the user.
     * @return array|false The user record or false if not found.
     */
    public function getUserById(int $id)
    {
        return $this->readOne(['user_id' => $id]);
    }

    /**
     * Retrieves a user by their email address.
     *
     * @param string $email The email of the user.
     * @return array|false The user record or false if not found.
     */
    public function getUserByEmail(string $email)
    {
        return $this->readOne(['email' => $email]);
    }

    /**
     * Retrieves users by their role.
     *
     * @param string $role The role of the users.
     * @param array $options Additional options like order, limit, and offset.
     * @return array|false List of users or false on failure.
     */
    public function getUsersByRole(string $role, array $options = [])
    {
        return $this->read(['role' => $role], $options);
    }

    /**
     * Creates a new user.
     *
     * @param array $data The user data to insert.
     * @return bool Success or failure of the operation.
     */
    public function createUser(array $data)
    {
        return $this->create($data);
    }

    /**
     * Updates a user by their ID.
     *
     * @param int $id The ID of the user to update.
     * @param array $data The data to update.
     * @return bool Success or failure of the operation.
     */
    public function updateUserById(int $id, array $data)
    {
        return $this->update(['user_id' => $id], $data);
    }

    /**
     * Deletes a user by their ID.
     *
     * @param int $id The ID of the user to delete.
     * @return bool Success or failure of the operation.
     */
    public function deleteUserById(int $id)
    {
        return $this->delete(['user_id' => $id]);
    }

    /**
     * Searches users by their name or email.
     *
     * @param string $searchTerm The search term for name or email.
     * @param array $options Additional options like order, limit, and offset.
     * @return array|false List of users or false on failure.
     */
    public function searchUsers(string $searchTerm, array $options = [])
    {
        $options['search'] = $searchTerm;
        $options['searchColumns'] = ['name', 'email'];
        return $this->read([], $options);
    }

    /**
     * Retrieves users with a specific account status.
     *
     * @param string $status The account status (active, inactive, suspended).
     * @param array $options Additional options like order, limit, and offset.
     * @return array|false List of users or false on failure.
     */
    public function getUsersByStatus(string $status, array $options = [])
    {
        return $this->read(['account_status' => $status], $options);
    }

    public function emailExists(string $email): bool
    {
        $query = "SELECT COUNT(*) FROM {$this->table} WHERE email = :email";
        $params = [':email' => $email];
        
        $count = $this->executeCustomQuery($query, $params);
        return $count > 0;
    }

}