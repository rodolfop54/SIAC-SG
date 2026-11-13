<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('document_type', 4)->nullable();
            $table->text('document_number')->nullable();
            $table->string('document_key', 64)->nullable()->unique();
            $table->timestampTz('web_registered_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['document_key']);
            $table->dropColumn(['document_type', 'document_number', 'document_key', 'web_registered_at']);
        });
    }
};
