<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('provider_id')->nullable()->constrained('providers')->nullOnDelete();
            $table->foreignId('fallback_provider_id')->nullable()->constrained('providers')->nullOnDelete();
            $table->string('status')->default('pending');
            $table->string('priority')->default('default');
            $table->string('type')->default('transactional');
            $table->string('subject');
            $table->json('to');
            $table->json('cc')->nullable();
            $table->json('bcc')->nullable();
            $table->longText('html')->nullable();
            $table->longText('text_content')->nullable();
            $table->json('attachments')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->unsignedInteger('retry_count')->default(0);
            $table->text('error_message')->nullable();
            $table->json('provider_response')->nullable();
            $table->string('queue_name')->nullable();
            $table->string('idempotency_key')->nullable()->unique();
            $table->string('template_slug')->nullable();
            $table->json('template_data')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
            $table->index(['application_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_logs');
    }
};
