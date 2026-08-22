<?php

namespace Tests\Unit;

use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function ユーザーは複数のレビューを持つことができる()
    {
        $user = User::factory()->create();
        $review = Review::factory()->create(['user_id' => $user->id]);

        // hasMany リレーションの検証
        $this->assertTrue($user->reviews->contains($review));
        $this->assertInstanceOf('Illuminate\Database\Eloquent\Collection', $user->reviews);
    }
}
