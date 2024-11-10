<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\VerificationCode;
use App\Notifications\VerificationCodeNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Response;

class AuthController extends Controller
{
    private const TWO_FACTOR_TYPE = '2fa';
    private const TWO_FACTOR_MESSAGE = 'Your 2FA code is: ';
    private const PASSWORD_RESET_TYPE = 'password_reset';
    private const PASSWORD_RESET_MESSAGE = 'Your password reset code is: ';

    public function me(): JsonResponse
    {
        return Response::ok([
            'user' => UserResource::make(Auth::user()),
        ]);
    }

    // Authentication Methods
    public function login(Request $request): JsonResponse
    {
        $credentials = $this->validateCredentials($request);
        $user = $this->findUser($credentials['email']);

        if (!$this->validateUserAndPassword($user, $credentials['password'])) {
            return $this->invalidCredentialsResponse();
        }

        // Log in the user
        Auth::login($user, true);

        if ($this->isTwoFactorEnabled($user)) {
            return $this->handleTwoFactorAuthentication($request, $user);
        }

        $request->session()->regenerate();
        return Response::notifyOk(['user' => $user], 'Login successful');
    }

    public function apiLogin(Request $request): JsonResponse
    {
        $credentials = $this->validateCredentials($request);
        $user = $this->findUser($credentials['email']);

        if (!$this->validateUserAndPassword($user, $credentials['password'])) {
            return $this->invalidCredentialsResponse();
        }

        if ($this->isTwoFactorEnabled($user)) {
            return $this->handleTwoFactorAuthentication($request, $user);
        }

        $token = $user->createToken('personal-token')->plainTextToken;

        return Response::notifyOk(['token' => $token], 'Login successful');
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Response::notifyOk([], 'Logged out successfully');
    }

    public function apiLogout(): JsonResponse
    {
        $user = Auth::user();
        if ($user) {
            $user->tokens()->delete();

            return Response::notifyOk([], 'Logged out successfully.');
        }

        return Response::notifyError(
            message: 'No authenticated user found.',
            status: 401
        );
    }

    // Two-Factor Authentication Methods
    public function verifyTwoFactor(Request $request): JsonResponse
    {
        return $this->verifyTwoFactorCode($request, function ($user) {
            $token = $user->createToken('personal-token')->plainTextToken;

            return Response::notifyOk(['token' => $token], 'Two-factor authentication successful.');
        });
    }

    public function verifyTwoFactorForSpa(Request $request): JsonResponse
    {
        return $this->verifyTwoFactorCode($request, function ($user) use ($request) {
            Auth::login($user, true);
            $request->session()->regenerate();

            return Response::notifyOk(['user' => Auth::user()], 'Two-factor authentication successful.');
        });
    }

    // Password Reset Methods
    public function requestPasswordReset(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|string']);

        $user = $this->findUser($request->email);

        if ($user) {
            $this->generateVerificationCode($user, self::PASSWORD_RESET_TYPE, self::PASSWORD_RESET_MESSAGE);
        }

        // Return a generic response
        return Response::notifyOk([], 'If the account exists, a verification code has been sent.');
    }
    public function verifyCodeAndResetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|string',
            'verification_code' => 'required|numeric',
            'password' => 'required|min:8|confirmed',
        ]);

        // Whether or not the personal exists, return the same response.
        $user = $this->findUser($request->email);

        if ($user) {
            $verificationCode = $this->validateVerificationCode($user, $request->verification_code, self::PASSWORD_RESET_TYPE);

            if ($verificationCode) {
                // If everything is valid, reset the password
                $user->update(['password' => bcrypt($request->password)]);
                $verificationCode->delete();

                return Response::notifyOk([], 'Password reset successfully. You can now log in with your new password.');
            }
        }

        // Return a generic response to avoid exposing information
        return Response::notifyError('Invalid or expired verification code. If your code is expired, we’ll send you a new one.');
    }


    // Helper Methods
    private function validateCredentials(Request $request): array
    {
        return $request->validate([
            'email' => ['required', 'string'],
            'password' => ['required'],
        ]);
    }

    private function findUser(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    private function isTwoFactorEnabled($user): bool
    {
        return env('TWO_FACTOR_AUTH', false) && $user->tfa;
    }

    private function handleTwoFactorAuthentication(Request $request, User $user): JsonResponse
    {
        $this->generateVerificationCode($user, self::TWO_FACTOR_TYPE, self::TWO_FACTOR_MESSAGE);

        if(Auth::user()){
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        $mask = substr($user->email, 0, 3) . '***';
        return Response::notifyOk(
            data: ['two_factor_required' => true],
            message: "A two-factor authentication code has been sent to your email: {$mask}.",
            notifyType: 'info'
        );
    }

    private function verifyTwoFactorCode(Request $request, callable $onSuccess): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'string'],
            'two_factor_code' => ['required', 'numeric'],
        ]);

        $user = $this->findUser($request->email);
        if (!$user) {
            return Response::notifyNotFound('User not found.', 404);
        }

        $verificationCode = $this->validateVerificationCode($user, $request->two_factor_code, self::TWO_FACTOR_TYPE);
        if (!$verificationCode) {
            return Response::notifyError('Invalid or expired verification code. We\'ll send you a new one if your code is expired.');
        }

        $verificationCode->delete();
        return $onSuccess($user);
    }

    private function validateUserAndPassword(?User $user, string $password): bool
    {
        return $user && Hash::check($password, $user->password);
    }

    private function generateVerificationCode(User $user, $type, $message): void
    {
        $code = rand(100000, 999999);

        VerificationCode::updateOrCreate(
            ['user_id' => $user->id, 'type' => $type],
            ['code' => $code, 'expires_at' => now()->addMinutes(10)]
        );

        $this->sendCode($user, "$message $code");
    }

    private function sendCode(User $user, string $message): void
    {
        $user->notify(new VerificationCodeNotification($message));
    }

    private function validateVerificationCode(User $user, string $code, string $type): ?VerificationCode
    {
        $verificationCode = VerificationCode::where('user_id', $user->id)
            ->where('type', $type)
            ->first();

        if (!$verificationCode || $verificationCode->code !== $code) {
            return null;
        }

        if ($verificationCode->isExpired()) {
            $this->generateVerificationCode($user, $type, $this->getVerificationMessage($type));
            return null;
        }

        return $verificationCode;
    }

    private function getVerificationMessage(string $type): string
    {
        return $type === self::TWO_FACTOR_TYPE ? self::TWO_FACTOR_MESSAGE : self::PASSWORD_RESET_MESSAGE;
    }

    private function invalidCredentialsResponse(): JsonResponse
    {
        return Response::notifyError('The provided credentials do not match our records.');
    }
}
