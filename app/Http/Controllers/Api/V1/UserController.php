<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    protected UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function updateUsername(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $user = $this->userService->updateUsername(auth()->id(), $request->name);

        return Response::notifyOk(
            data:['user' => $user],
            message: 'Username updated successfully'
        );
    }

    /**
     * @throws ValidationException
     */
    public function updateEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string',
        ]);

        $user = $this->userService->updateEmail(auth()->id(), $request->email, $request->password);

        return Response::notifyOk(
            data:['user' => $user],
            message: 'Email updated successfully'
        );
    }

    /**
     * @throws ValidationException
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $user = $this->userService->updatePassword(auth()->id(), $request->password, $request->new_password);

        return Response::notifyOk(
            data:['user' => $user],
            message: 'Password updated successfully'
        );
    }

    // 5. Update Two-Factor Authentication (TFA)
    public function updateTfa(Request $request)
    {
        $request->validate([
            'tfa' => 'required|boolean',
        ]);

        $user = $this->userService->updateTfa(auth()->id(), $request->tfa);

        return Response::notifyOk(
            data: ['user' => $user],
            message: 'Two-Factor Authentication updated successfully.',
        );
    }
}
