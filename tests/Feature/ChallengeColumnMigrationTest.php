<?php

namespace Whilesmart\Forms\Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Whilesmart\Forms\Models\Form;
use Whilesmart\Forms\Tests\TestCase;

class ChallengeColumnMigrationTest extends TestCase
{
    private const MIGRATION = __DIR__ . '/../../database/migrations/2026_08_01_000001_add_challenge_to_forms_table.php';

    private function runMigration(): void
    {
        (require self::MIGRATION)->up();
    }

    #[Test]
    public function it_is_a_no_op_when_the_column_is_already_present(): void
    {
        $form = Form::create(['key' => 'contact', 'challenge' => ['driver' => 'turnstile']]);

        $this->runMigration();

        $this->assertTrue(Schema::hasColumn('forms', 'challenge'));
        $this->assertSame(['driver' => 'turnstile'], $form->fresh()->challenge);
    }

    #[Test]
    public function it_adds_the_column_when_an_older_table_lacks_it(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            $table->dropColumn('challenge');
        });
        $this->assertFalse(Schema::hasColumn('forms', 'challenge'));

        $this->runMigration();

        $this->assertTrue(Schema::hasColumn('forms', 'challenge'));

        Form::create(['key' => 'contact', 'challenge' => ['driver' => null]]);
        $this->assertSame(['driver' => null], Form::first()->challenge);
    }

    #[Test]
    public function it_widens_a_single_driver_column_and_carries_its_values(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            $table->dropColumn('challenge');
        });
        Schema::table('forms', function (Blueprint $table) {
            $table->string('challenge')->nullable();
        });

        DB::table('forms')->insert([
            ['key' => 'contact', 'challenge' => 'turnstile', 'is_active' => true],
            ['key' => 'mobile', 'challenge' => 'none', 'is_active' => true],
            ['key' => 'inherits', 'challenge' => null, 'is_active' => true],
        ]);

        $this->runMigration();

        $this->assertSame(['driver' => 'turnstile'], Form::where('key', 'contact')->first()->challenge);
        $this->assertSame(['driver' => null], Form::where('key', 'mobile')->first()->challenge);
        $this->assertNull(Form::where('key', 'inherits')->first()->challenge);
    }
}
