<?php

namespace Tests\Feature;

use Tests\TestCase;

class StudentWebAppTest extends TestCase
{
    public function test_root_redirects_to_the_student_app(): void
    {
        $this->get('/')->assertRedirect('/app/');
    }

    public function test_student_app_is_served_for_client_side_paths(): void
    {
        foreach (['/app/', '/app/some/deep/link'] as $url) {
            $response = $this->get($url)->assertOk();
            $this->assertSame(public_path('app/index.html'), $response->baseResponse->getFile()->getPathname());
        }

        $this->assertStringContainsString('flutter_bootstrap.js', file_get_contents(public_path('app/index.html')));
    }
}
