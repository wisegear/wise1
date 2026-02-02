<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $renderedLayout = view('layouts.app')->render();

        $this->assertStringContainsString(
            'https://wa.me/447720868799?text=Hi%20Lee%2C%20I%27m%20contacting%20you%20about%20propertyresearch.uk',
            $renderedLayout
        );
        $this->assertStringNotContainsString('https://x.com/Propertyda03', $renderedLayout);
    }
}
