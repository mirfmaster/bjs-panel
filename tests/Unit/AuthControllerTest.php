<?php

namespace Tests\Unit;

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function test_show_login_returns_view(): void
    {
        $controller = new AuthController();
        $view = $controller->showLogin();

        $this->assertInstanceOf(View::class, $view);
        $this->assertEquals('auth.login', $view->getName());
    }

    public function test_login_validates_required_fields(): void
    {
        $controller = new AuthController();

        $request = new Request([
            'email' => '',
            'password' => '',
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $controller->login($request);
    }

    public function test_login_validates_email_format(): void
    {
        $controller = new AuthController();

        $request = new Request([
            'email' => 'not-an-email',
            'password' => 'secret',
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $controller->login($request);
    }
}
