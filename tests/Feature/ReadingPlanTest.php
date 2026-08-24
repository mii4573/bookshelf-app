<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingPlanTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_reading_plans_index()
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('reading-plans.index'));
        $response->assertStatus(200);
    }

    public function test_user_can_create_reading_plan()
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)->post(route('reading-plans.store'), [
            'book_id' => $book->id,
            'target_date' => now()->addDays(5)->toDateString(),
        ]);

        $response->assertRedirect(route('reading-plans.index'));
        $this->assertDatabaseHas('reading_plans', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);
    }

    public function test_user_can_update_reading_plan()
    {
        $user = User::factory()->create();
        $plan = ReadingPlan::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->put(route('reading-plans.update', $plan), [
            'book_id' => $plan->book_id,
            'target_date' => now()->addDays(10)->toDateString(),
        ]);

        $response->assertRedirect(route('reading-plans.index'));
    }

    public function test_user_can_complete_reading_plan()
    {
        $user = User::factory()->create();
        $plan = ReadingPlan::factory()->create(['user_id' => $user->id, 'status' => 'in_progress']);

        $response = $this->actingAs($user)->post(route('reading-plans.complete', $plan));

        $response->assertRedirect();
        $this->assertEquals('completed', $plan->fresh()->status->value ?? $plan->fresh()->status);
    }

    public function test_user_can_delete_reading_plan()
    {
        $user = User::factory()->create();
        $plan = ReadingPlan::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->delete(route('reading-plans.destroy', $plan));

        $response->assertRedirect(route('reading-plans.index'));
        $this->assertDatabaseMissing('reading_plans', ['id' => $plan->id]);
    }

    public function test_other_user_cannot_update_reading_plan()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $plan = ReadingPlan::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)->put(route('reading-plans.update', $plan), [
            'book_id' => $plan->book_id,
            'target_date' => now()->addDays(5)->toDateString(),
        ]);

        $response->assertStatus(403); // 権限エラーになることを確認
    }

    public function test_other_user_cannot_delete_reading_plan()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $plan = ReadingPlan::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)->delete(route('reading-plans.destroy', $plan));

        $response->assertStatus(403);
    }
}
