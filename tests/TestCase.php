<?php

namespace Whilesmart\Forms\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\Attributes\WithMigration;

#[WithMigration]
abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app)
    {
        return [
            \Whilesmart\Forms\FormsServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app)
    {
        // Run the fan-out inline and capture mail so tests observe outcomes.
        $app['config']->set('queue.default', 'sync');
        $app['config']->set('mail.default', 'array');
        $app['config']->set('eloquent-forms.mail.to', 'ops@example.com');
    }
}
