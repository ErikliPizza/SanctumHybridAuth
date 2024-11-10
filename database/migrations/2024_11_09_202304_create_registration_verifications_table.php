<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // you can merge this table with verification codes table or maybe create a new polymorphic table instead of both based on your requirements.
        Schema::create('registration_verifications', function (Blueprint $table) {
            $table->id();
            $table->string('contact');
            $table->string('verification_code');
            $table->timestamp('expires_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registration_verifications');
    }
};
