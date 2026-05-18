<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('providers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->string('type');
            $table->string('status')->default('active');
            $table->unsignedInteger('priority')->default(100);
            $table->text('config');
            $table->string('health_status')->default('healthy');
            $table->unsignedInteger('quota_limit')->nullable();
            $table->unsignedInteger('quota_used')->default(0);
            $table->unsignedInteger('timeout')->default(30);
            $table->unsignedInteger('weight')->default(1);
            $table->timestamp('last_health_check_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('providers');
    }
};
