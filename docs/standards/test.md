# Test Standards

## PHPUnit
- Use `Illuminate\Foundation\Testing\TestCase` for Laravel integration
- Mock dependencies via constructor injection
- Test behavior, not implementation details
- Single test file per component when appropriate
- Use Mockery for mocking (`\Mockery::mock()`)

## Test Structure
```php
class {Component}Test extends TestCase
{
    protected function setUp(): void { /* ... */ }
    protected function tearDown(): void { /* ... */ }

    public function test_{scenario}(): void { /* ... */ }
}
```

## Test Categories
| Category | Location | Purpose |
|----------|----------|---------|
| Unit | `tests/Unit/` | Isolated component tests |
| Feature | `tests/Feature/` | Integration tests |
| Seeder | `tests/Unit/` | Seeder/cache population tests |

## Mocking Pattern
```php
private function createMockCache(): CacheManager
{
    $cache = \Mockery::mock(CacheManager::class);
    $cache->shouldReceive('get')->andReturnUsing(function ($key, $default = null) {
        return $this->cacheData[$key] ?? $default;
    });
    $cache->shouldReceive('put')->andReturnUsing(function ($key, $value) {
        $this->cacheData[$key] = $value;
    });
    return $cache;
}
```

## Running Tests
```bash
php artisan test tests/Unit/{Component}Test.php
```

## Test Expectations
- All tests must pass before pushing
- Minimum 80% coverage for new code
- Use Gherkin format in `docs/TED/` for feature descriptions
