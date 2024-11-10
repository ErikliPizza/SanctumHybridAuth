<?php
namespace App\Http\Controllers\Api\V1\Auth;

use App\Helpers\NameGenerator;
use App\Http\Controllers\Controller;
use App\Models\RegistrationVerification;
use App\Models\User;
use App\Notifications\VerificationCodeNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Response;
class RegisterController extends Controller
{
    private const PRE_REGISTER_CODE_MESSAGE = 'Your verification code to register is: ';

    public function preRegister(Request $request)
    {
        $request->validate([
            'email' => ['required', 'string', 'email', 'unique:users,email'],
            'password' => ['required', 'string'],
        ]);

        $email = $request->input('email');
        $code = rand(100000, 999999);

        RegistrationVerification::updateOrCreate(
            ['contact' => $email],
            [
                'verification_code' => $code,
                'expires_at' => now()->addMinutes(10)
            ]
        );

        Notification::route('mail', $email)
            ->notify(new VerificationCodeNotification(self::PRE_REGISTER_CODE_MESSAGE . $code));

        return Response::notifyOk(
            data: ['two_factor_required' => true],
            message: "A verification code has been sent to your email.",
            notifyType: 'info'
        );
    }

    public function register(Request $request)
    {
        $request->validate([
            'email' => ['required', 'string', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'code' => ['required', 'digits:6'],
        ]);

        // Get the contact (either phone or email)
        $email = $request->input('email');

        // Check if the verification entry exists and is not expired
        $verification = RegistrationVerification::where('contact', $email)
            ->where('verification_code', $request->input('code'))
            ->first();

        if (!$verification || $verification->isExpired()) {
            return Response::notifyError('Invalid or expired verification code.');
        }

        // Create the user
        User::create([
            'name' => NameGenerator::Funny(),
            'email' => $request->input('email'),
            'password' => bcrypt($request->input('password')), // Encrypt the password
            'email_verified_at' => now(),
        ]);

        // Optionally, delete the verification record after successful registration
        $verification->delete();

        return Response::notifyOk(message: "Your account has been created. You can now login.");
    }
}
