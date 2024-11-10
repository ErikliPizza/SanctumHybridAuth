<?php

namespace App\Services;

use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserService
{
    protected UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }
    public function updateUsername($userId, $name)
    {
        return $this->userRepository->updateField($userId, 'name', $name);
    }

    /**
     * @throws ValidationException
     */
    public function updateEmail($userId, $email, $password)
    {
        $user = $this->userRepository->findById($userId);

        if (!Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['The provided password is incorrect.'],
            ]);
        }

        return $this->userRepository->updateField($userId, 'email', $email);
    }

    /**
     * @throws ValidationException
     */
    public function updatePassword($userId, $password, $newPassword)
    {
        $user = $this->userRepository->findById($userId);

        if (!Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['The provided password is incorrect.'],
            ]);
        }

        return $this->userRepository->updateField($userId, 'password', Hash::make($newPassword));
    }
    public function updateTfa($userId, $tfa)
    {
        return $this->userRepository->updateField($userId, 'tfa', $tfa);
    }
}
