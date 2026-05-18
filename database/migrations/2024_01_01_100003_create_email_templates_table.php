<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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
    }

    public function down(): void
    {
        Schema::dropIfExists('email_templates');
    }
};
