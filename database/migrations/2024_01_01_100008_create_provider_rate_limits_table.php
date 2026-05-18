<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_rate_limits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained()->cascadeOnDelete();
            $table->string('period');
            $table->unsignedInteger('limit');
            $table->unsignedInteger('used')->default(0);
            $table->timestamp('resets_at');
            $table->timestamps();
            $table->unique(['provider_id', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_rate_limits');
    }
};
