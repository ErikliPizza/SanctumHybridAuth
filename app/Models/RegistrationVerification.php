<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class RegistrationVerification extends Model
{
    use HasFactory;

    // Specify the table name, in case it's not automatically detected
    protected $table = 'registration_verifications';

    // Specify the fillable columns to allow mass assignment
    protected $fillable = [
        'contact',
        'verification_code',
        'expires_at',
    ];

    // Indicate that 'expires_at' is a date column
    protected $dates = [
        'expires_at',
    ];

    /**
     * Check if the verification code is expired.
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        return Carbon::now()->greaterThan($this->expires_at);
    }
}
