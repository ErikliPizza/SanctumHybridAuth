<?php

namespace App\Repositories;

use App\Models\User;

class UserRepository
{
    protected User $model;

    public function __construct(User $user)
    {
        $this->model = $user;
    }

    public function findById($userId)
    {
        return $this->model->findOrFail($userId);
    }
    public function updateField($userId, $field, $value)
    {
        $user = $this->findById($userId);
        $user->$field = $value;
        $user->save();

        return $user;
    }
}
