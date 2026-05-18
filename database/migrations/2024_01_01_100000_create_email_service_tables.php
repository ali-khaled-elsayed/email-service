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

        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('subject');
            $table->longText('html_template');
            $table->json('variables')->nullable();
            $table->timestamps();
            $table->unique(['application_id', 'slug']);
        });

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

        Schema::create('email_status_timelines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_log_id')->constrained()->cascadeOnDelete();
            $table->string('old_status')->nullable();
            $table->string('new_status');
            $table->string('message')->nullable();
            $table->timestamps();
        });

        Schema::create('provider_health_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained()->cascadeOnDelete();
            $table->string('status');
            $table->unsignedInteger('latency_ms')->nullable();
            $table->text('message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

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

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->string('log_name')->nullable();
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('event');
            $table->text('description')->nullable();
            $table->json('properties')->nullable();
            $table->string('causer_type')->nullable();
            $table->unsignedBigInteger('causer_id')->nullable();
            $table->timestamps();
            $table->index(['subject_type', 'subject_id']);
        });

        Schema::create('api_request_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->nullable()->constrained()->nullOnDelete();
            $table->string('method');
            $table->string('path');
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->json('request_body')->nullable();
            $table->json('response_body')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_request_logs');
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('email_metrics');
        Schema::dropIfExists('provider_rate_limits');
        Schema::dropIfExists('provider_health_logs');
        Schema::dropIfExists('email_status_timelines');
        Schema::dropIfExists('failed_email_attempts');
        Schema::dropIfExists('email_logs');
        Schema::dropIfExists('email_templates');
        Schema::dropIfExists('applications');
        Schema::dropIfExists('providers');
    }
};
