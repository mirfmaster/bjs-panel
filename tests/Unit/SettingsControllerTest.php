<?php

namespace Tests\Unit;

use App\Http\Controllers\SettingsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Tests\TestCase;

class SettingsControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    private function mockSuperadminUser(): void
    {
        $user = new \stdClass();
        $user->is_superadmin = true;
        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('user')->andReturn($user);
    }

    private function mockRegularUser(): void
    {
        $user = new \stdClass();
        $user->is_superadmin = false;
        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('user')->andReturn($user);
    }

    public function test_non_superadmin_redirects_from_index(): void
    {
        $this->mockRegularUser();

        $controller = new SettingsController();
        $response = $controller->index();

        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
        $this->assertStringContainsString('dashboard', $response->getTargetUrl());
    }

    public function test_non_superadmin_redirects_from_update(): void
    {
        $this->mockRegularUser();

        $controller = new SettingsController();
        $request = Request::create('/settings', 'PUT', [
            'username' => 'test',
            'password' => 'test',
        ]);

        $response = $controller->update($request);

        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
        $this->assertStringContainsString('dashboard', $response->getTargetUrl());
    }

    public function test_superadmin_can_access_index(): void
    {
        $this->mockSuperadminUser();

        \Illuminate\Support\Facades\Cache::shouldReceive('get')
            ->with('bjs.credentials.username', null)
            ->andReturn('testuser');
        \Illuminate\Support\Facades\Cache::shouldReceive('get')
            ->with('bjs.credentials.password', null)
            ->andReturn('testpass');
        \Illuminate\Support\Facades\Cache::shouldReceive('get')
            ->with('bjs.session.login_toggle', false)
            ->andReturn(true);
        \Illuminate\Support\Facades\Cache::shouldReceive('get')
            ->with('bjs.services', null)
            ->andReturn([195, 190, 91]);

        $controller = new SettingsController();
        $view = $controller->index();

        $this->assertInstanceOf(View::class, $view);
        $this->assertEquals('settings', $view->getName());
        $this->assertEquals('testuser', $view->getData()['settings']['username']);
        $this->assertEquals('testpass', $view->getData()['settings']['password']);
        $this->assertTrue($view->getData()['settings']['login_toggle']);
    }

    public function test_superadmin_can_access_index_with_defaults(): void
    {
        $this->mockSuperadminUser();

        \Illuminate\Support\Facades\Cache::shouldReceive('get')
            ->with('bjs.credentials.username', null)
            ->andReturn('');
        \Illuminate\Support\Facades\Cache::shouldReceive('get')
            ->with('bjs.credentials.password', null)
            ->andReturn('');
        \Illuminate\Support\Facades\Cache::shouldReceive('get')
            ->with('bjs.session.login_toggle', false)
            ->andReturn(false);
        \Illuminate\Support\Facades\Cache::shouldReceive('get')
            ->with('bjs.services', null)
            ->andReturn([]);

        $controller = new SettingsController();
        $view = $controller->index();

        $this->assertInstanceOf(View::class, $view);
        $this->assertFalse($view->getData()['settings']['login_toggle']);
    }

    public function test_update_validates_username_required(): void
    {
        $this->mockSuperadminUser();

        $controller = new SettingsController();
        $request = Request::create('/settings', 'PUT', [
            'username' => '',
            'password' => 'newpass',
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $controller->update($request);
    }

    public function test_update_validates_password_required(): void
    {
        $this->mockSuperadminUser();

        $controller = new SettingsController();
        $request = Request::create('/settings', 'PUT', [
            'username' => 'newuser',
            'password' => '',
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $controller->update($request);
    }
}
