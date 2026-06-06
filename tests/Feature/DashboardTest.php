<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_authenticated_users_are_sent_to_the_marketing_panel()
    {
        $this->actingAs(User::factory()->create());

        $this->get('/dashboard')->assertRedirect('/admin');
    }

    public function test_authenticated_users_can_visit_the_marketing_dashboard()
    {
        $this->actingAs(User::factory()->create());

        $this->get('/admin')->assertOk();
    }
}
