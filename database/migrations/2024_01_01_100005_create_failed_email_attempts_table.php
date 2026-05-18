<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('failed_email_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_log_id')->constrained()->cascadeOnDelete();
            $table->foreignId('provider_id')->nullable()->constrained('providers')->nullOnDelete();
            $table->text('exception');
            $table->longText('stack_trace')->nullable();
            $table->boolean('retryable')->default(false);
            $table->unsignedInteger('attempt_number')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('failed_email_attempts');
    }
};
