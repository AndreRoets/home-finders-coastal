<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_registration_is_disabled(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register', [])->assertNotFound();
    }

    public function test_admin_can_create_a_user_who_can_then_log_in(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post('/admin/users', [
            'name' => 'New Marketer',
            'email' => 'marketer@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertRedirect('/admin/users');

        $this->assertDatabaseHas('users', ['email' => 'marketer@example.com']);

        $user = User::query()->where('email', 'marketer@example.com')->firstOrFail();
        $this->assertTrue(Hash::check('Password123!', $user->password));
    }

    public function test_admin_can_update_a_user_without_changing_the_password(): void
    {
        $this->actingAs(User::factory()->create());
        $target = User::factory()->create(['name' => 'Old Name']);
        $originalPassword = $target->password;

        $this->put("/admin/users/{$target->id}", [
            'name' => 'Updated Name',
            'email' => $target->email,
            'password' => '',
            'password_confirmation' => '',
        ])->assertRedirect('/admin/users');

        $target->refresh();
        $this->assertSame('Updated Name', $target->name);
        $this->assertSame($originalPassword, $target->password);
    }

    public function test_a_user_cannot_delete_their_own_account(): void
    {
        $user = User::factory()->create();
        User::factory()->create();
        $this->actingAs($user);

        $this->delete("/admin/users/{$user->id}")->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_the_final_remaining_user_cannot_be_deleted(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->delete("/admin/users/{$user->id}")->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_admin_can_delete_another_user(): void
    {
        $this->actingAs(User::factory()->create());
        $target = User::factory()->create();

        $this->delete("/admin/users/{$target->id}")->assertRedirect('/admin/users');

        $this->assertDatabaseMissing('users', ['id' => $target->id]);
    }
}
