<?php

namespace Tests\Feature;

use Tests\TestCase;

class AppTimezoneTest extends TestCase
{
    public function test_the_application_runs_on_casablanca_time(): void
    {
        $this->assertSame('Africa/Casablanca', config('app.timezone'));
        $this->assertSame('Africa/Casablanca', date_default_timezone_get());
        $this->assertSame('Africa/Casablanca', now()->timezoneName);
    }
}
