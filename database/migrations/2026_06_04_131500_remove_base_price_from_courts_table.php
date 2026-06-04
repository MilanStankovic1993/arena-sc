<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('courts', 'base_price')) {
            return;
        }

        Schema::table('courts', function (Blueprint $table): void {
            $table->dropColumn('base_price');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('courts', 'base_price')) {
            return;
        }

        Schema::table('courts', function (Blueprint $table): void {
            $table->decimal('base_price', 10, 2)->default(0)->after('capacity');
        });
    }
};
