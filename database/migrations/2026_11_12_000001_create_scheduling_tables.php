<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $t) {
            $t->id();
            $t->string('name')->unique();
            $t->string('description');
            $t->unsignedSmallInteger('duration_minutes')->default(30);
            $t->boolean('enabled')->default(true);
            $t->timestamps();
        });
        Schema::create('slots', function (Blueprint $t) {
            $t->id();
            $t->foreignId('service_id')->constrained()->restrictOnDelete();
            $t->timestampTz('starts_at');
            $t->timestampTz('ends_at');
            $t->boolean('enabled')->default(true);
            $t->timestamps();
            $t->unique(['service_id', 'starts_at']);
            $t->index(['service_id', 'enabled', 'starts_at']);
        });
        DB::statement('ALTER TABLE slots ADD CONSTRAINT valid_slot_time CHECK (ends_at > starts_at)');
        Schema::create('appointments', function (Blueprint $t) {
            $t->id();
            $t->uuid('code')->unique();
            $t->foreignId('user_id')->constrained()->restrictOnDelete();
            $t->foreignId('slot_id')->constrained()->restrictOnDelete();
            $t->enum('status', ['CONFIRMADA', 'CANCELADA'])->default('CONFIRMADA');
            $t->uuid('request_key')->unique();
            $t->enum('channel', ['web', 'bot']);
            $t->timestampTz('cancelled_at')->nullable();
            $t->timestampsTz();
            $t->index(['user_id', 'status']);
            $t->index(['created_at', 'status']);
        });
        DB::statement("CREATE UNIQUE INDEX one_active_appointment_per_slot ON appointments (slot_id) WHERE status = 'CONFIRMADA'");
        Schema::create('audit_events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $t->string('entity');
            $t->unsignedBigInteger('entity_id');
            $t->string('operation');
            $t->jsonb('before')->nullable();
            $t->jsonb('after')->nullable();
            $t->string('channel');
            $t->timestampsTz();
            $t->index(['entity', 'entity_id']);
        });
        Schema::create('simulated_messages', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->restrictOnDelete();
            $t->foreignId('appointment_id')->nullable()->constrained()->restrictOnDelete();
            $t->string('sender');
            $t->string('type');
            $t->text('content');
            $t->jsonb('options')->nullable();
            $t->string('event_key')->nullable()->unique();
            $t->timestampTz('read_at')->nullable();
            $t->timestampsTz();
            $t->index(['user_id', 'id']);
        });
    }

    public function down(): void
    {
        foreach (['simulated_messages', 'audit_events', 'appointments', 'slots', 'services'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
