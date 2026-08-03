<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Brings an existing `forms` table up to the shape the create migration now
 * ships. Installs created after that change already have the column, so every
 * branch here is conditional and the migration is a no-op for them.
 */
return new class () extends Migration {
    /**
     * Column types that cannot hold a JSON document and so mark the older,
     * single-driver shape. A JSON column reports as `json` on MySQL and
     * Postgres but as `text` on SQLite, so the check names what must be
     * widened rather than what is already correct.
     */
    private const NARROW_TYPES = ['varchar', 'char', 'string'];

    public function up(): void
    {
        if (! Schema::hasColumn('forms', 'challenge')) {
            Schema::table('forms', function (Blueprint $table) {
                $table->json('challenge')->nullable()->after('allowed_origins');
            });

            return;
        }

        if (! in_array(Schema::getColumnType('forms', 'challenge'), self::NARROW_TYPES, true)) {
            return;
        }

        // One row per form definition, so holding them while the column is
        // swapped costs nothing and avoids a driver-specific rename.
        $existing = DB::table('forms')
            ->whereNotNull('challenge')
            ->pluck('challenge', 'id');

        Schema::table('forms', function (Blueprint $table) {
            $table->dropColumn('challenge');
        });

        Schema::table('forms', function (Blueprint $table) {
            $table->json('challenge')->nullable()->after('allowed_origins');
        });

        foreach ($existing as $id => $driver) {
            DB::table('forms')->where('id', $id)->update([
                'challenge' => json_encode([
                    'driver' => $driver === 'none' ? null : $driver,
                ]),
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('forms', 'challenge')) {
            Schema::table('forms', function (Blueprint $table) {
                $table->dropColumn('challenge');
            });
        }
    }
};
