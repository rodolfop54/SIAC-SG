<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('email')->nullable();
            $table->timestampTz('email_verified_at')->nullable();
            $table->timestampTz('password_set_at')->nullable();
            $table->unsignedInteger('auth_version')->default(0);
        });

        DB::table('users')->update(['password_set_at' => now()]);
        DB::table('users')->whereNotNull('document_key')->whereNull('web_registered_at')
            ->update(['web_registered_at' => now()]);

        Schema::create('patient_access_challenges', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('document_key', 64)->index();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('auth_version')->default(0);
            $table->string('purpose', 12);
            $table->text('payload');
            $table->string('token_hash', 64);
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestampTz('expires_at');
            $table->timestampTz('consumed_at')->nullable();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_access_challenges');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['email', 'email_verified_at', 'password_set_at', 'auth_version']);
        });
    }
};
