<?php

namespace Tests\Unit;

use App\Http\Controllers\SettingsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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

    public function test_index_returns_current_settings_from_cache(): void
    {
        Cache::shouldReceive('get')
            ->with('bjs.credentials.username', null)
            ->andReturn('testuser');
        Cache::shouldReceive('get')
            ->with('bjs.credentials.password', null)
            ->andReturn('testpass');
        Cache::shouldReceive('get')
            ->with('bjs.session.login_toggle', false)
            ->andReturn(true);

        $controller = new SettingsController();
        $view = $controller->index();

        $this->assertInstanceOf(View::class, $view);
        $this->assertEquals('settings', $view->getName());
        $this->assertEquals('testuser', $view->getData()['settings']['username']);
        $this->assertEquals('testpass', $view->getData()['settings']['password']);
        $this->assertTrue($view->getData()['settings']['login_toggle']);
    }

    public function test_index_returns_default_toggle_when_not_set(): void
    {
        Cache::shouldReceive('get')
            ->with('bjs.credentials.username', null)
            ->andReturn('');
        Cache::shouldReceive('get')
            ->with('bjs.credentials.password', null)
            ->andReturn('');
        Cache::shouldReceive('get')
            ->with('bjs.session.login_toggle', false)
            ->andReturn(false);

        $controller = new SettingsController();
        $view = $controller->index();

        $this->assertInstanceOf(View::class, $view);
        $this->assertFalse($view->getData()['settings']['login_toggle']);
    }

    public function test_update_validates_username_required(): void
    {
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
        $controller = new SettingsController();
        $request = Request::create('/settings', 'PUT', [
            'username' => 'newuser',
            'password' => '',
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $controller->update($request);
    }
}
