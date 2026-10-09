<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class DemoExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_uses_fictional_data_without_changing_the_application_database(): void
    {
        $user = User::factory()->create(['email' => 'private@example.test']);
        $path = 'storage/framework/testing-courses.json';
        try {
            $this->artisan('demo:export', ['path' => $path])->assertSuccessful();
            $json = File::get(base_path($path));
            $courses = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
            $this->assertCount(3, $courses);
            $this->assertCount(3, $courses[0]['lessons']);
            $this->assertStringNotContainsString($user->email, $json);
            $this->assertStringNotContainsString('password', $json);
            $this->assertDatabaseCount('users', 1);
            $this->assertDatabaseHas('users', ['id' => $user->id, 'email' => $user->email]);
        } finally {
            File::delete(base_path($path));
        }
    }
}
