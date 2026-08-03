<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('forms', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name')->nullable();
            $table->string('recipient_email')->nullable();
            $table->json('destinations')->nullable();
            $table->json('allowed_origins')->nullable();
            $table->json('challenge')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('meta')->nullable();
            $table->nullableMorphs('owner');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forms');
    }
};
