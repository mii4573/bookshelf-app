<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /*
    |--------------------------------------------------------------------------
    | 会員登録 (Register) テスト
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function ゲストユーザーは会員登録画面を表示できる()
    {
        $response = $this->get(route('register'));

        $response->assertOk();
        $response->assertViewIs('auth.register');
    }

    /** @test */
    public function 正常な情報で会員登録ができる()
    {
        $response = $this->post(route('register'), [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // データベースに保存されたか
        $this->assertDatabaseHas('users', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
        ]);

        // ログイン状態になり、Top/書籍一覧などへリダイレクトされるか
        $this->assertAuthenticated();
        $response->assertRedirect('/');

    }

    /** @test */
    public function 重複したメールアドレスでは会員登録できない()
    {
        User::factory()->create(['email' => 'test@example.com']);

        $response = $this->post(route('register'), [
            'name' => '別ユーザー',
            'email' => 'test@example.com', // 重複
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
    }

    /** @test */
    public function パスワードが8文字未満では会員登録できない()
    {
        $response = $this->post(route('register'), [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'short7', // 7文字
            'password_confirmation' => 'short7',
        ]);

        $response->assertSessionHasErrors(['password']);
        $this->assertGuest();
    }

    /*
    |--------------------------------------------------------------------------
    | ログイン (Login) テスト
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function ゲストユーザーはログイン画面を表示できる()
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertViewIs('auth.login');
    }

    /** @test */
    public function 正しい認証情報でログインができる()
    {
        $user = User::factory()->create([
            'email' => 'login@example.com',
            'password' => Hash::make('secret123'),
        ]);

        $response = $this->post(route('login'), [
            'email' => 'login@example.com',
            'password' => 'secret123',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/');
    }

    /** @test */
    public function 誤った認証情報ではログインできない()
    {
        User::factory()->create([
            'email' => 'login@example.com',
            'password' => Hash::make('secret123'),
        ]);

        $response = $this->post(route('login'), [
            'email' => 'login@example.com',
            'password' => 'wrong_password', // 誤ったパスワード
        ]);

        $response->assertSessionHasErrors();
        $this->assertGuest();
    }

    /*
    |--------------------------------------------------------------------------
    | ログアウト (Logout) テスト
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function ログインユーザーはログアウトできる()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('logout'));

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
