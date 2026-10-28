<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', fn (Blueprint $table) => $table->softDeletes());
        Schema::create('reservation_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('actor_id')->nullable()->index();
            $table->string('actor_name');
            $table->string('actor_type', 20);
            $table->string('event', 40);
            $table->json('old_values');
            $table->json('new_values');
            $table->timestamp('created_at')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservation_audits');
        Schema::table('reservations', fn (Blueprint $table) => $table->dropSoftDeletes());
    }
};
