<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            // Null inherits the configured default; 'none' opts this form out,
            // which is what an API-only form wants.
            $table->string('challenge')->nullable()->after('allowed_origins');
        });
    }

    public function down(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            $table->dropColumn('challenge');
        });
    }
};
