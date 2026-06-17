<?php

namespace Tests\Feature;

use Tests\TestCase;

class KnownLimitationsPageTest extends TestCase
{
    public function test_known_limitations_page_is_publicly_accessible(): void
    {
        $this->get(route('limitations'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('KnownLimitations'));
    }
}
