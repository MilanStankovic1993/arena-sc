<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_canonical_and_json_ld_do_not_trust_request_host_or_raw_script_content(): void
    {
        config([
            'app.url' => 'https://scarena.rs',
            'arena.seo.site_name' => '</script><script>alert(1)</script>',
        ]);

        $response = $this
            ->withServerVariables(['HTTP_HOST' => 'attacker.example'])
            ->get('http://attacker.example/');

        $response
            ->assertOk()
            ->assertSee('<link rel="canonical" href="https://scarena.rs/">', false)
            ->assertDontSee('<link rel="canonical" href="http://attacker.example/">', false)
            ->assertDontSee('</script><script>alert(1)</script>', false);
    }
}
