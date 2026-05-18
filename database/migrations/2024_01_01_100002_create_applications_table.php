<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('app_key')->unique();
            $table->string('status')->default('active');
            $table->foreignId('default_provider_id')->nullable()->constrained('providers')->nullOnDelete();
            $table->foreignId('fallback_provider_id')->nullable()->constrained('providers')->nullOnDelete();
            $table->unsignedInteger('rate_limit')->default(100);
            $table->json('settings')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
