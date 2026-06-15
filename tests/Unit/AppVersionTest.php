<?php

namespace Tests\Unit;

use Tests\TestCase;

class AppVersionTest extends TestCase
{
    public function test_version_matches_version_file(): void
    {
        $expected = trim((string) file_get_contents(base_path('VERSION')));

        $this->assertSame($expected, config('app.version'));
    }

    public function test_version_is_valid_semver(): void
    {
        $this->assertMatchesRegularExpression(
            '/^\d+\.\d+\.\d+(-[\w.]+)?(\+[\w.]+)?$/',
            config('app.version'),
        );
    }
}
