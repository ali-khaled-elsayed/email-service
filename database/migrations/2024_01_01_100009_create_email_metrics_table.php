<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_metrics', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->foreignId('application_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('provider_id')->nullable()->constrained()->nullOnDelete();
            $table->string('metric');
            $table->unsignedBigInteger('value')->default(0);
            $table->json('breakdown')->nullable();
            $table->timestamps();
            $table->unique(['date', 'application_id', 'provider_id', 'metric']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_metrics');
    }
};
