# Code Standards

## Code Standards
- Best practices + pragmatism > over-engineering
- Functional + testable > abstract + complex

## Git Workflow (MANDATORY)
- Use `bd` (beads) for issue tracking
- Session end checklist:
  1. Run quality gates (`php artisan pint`, `php artisan test`)
  2. Commit changes with meaningful message
  3. **PUSH to remote** (NOT optional)
  4. Verify `git status` shows "up to date with origin"
  5. Update issue statuses (`bd close`)

## PHP/Laravel
- Follow Laravel naming: `StudlyCase` classes, `camelCase` methods
- **No comments** unless explicitly requested
- Type hints for all parameters and return values
- Constructor injection for dependencies (testability)
- **Skip interfaces** when single implementation exists
- Consolidate classes when single responsibility isn't critical
- Use native PHP enums (8.1+) for status/constants
- Use `Config` facade for application settings
- Use `Cache` facade for caching (NOT direct Redis)

## Error Handling
- Use custom exceptions extending `App\Exceptions\Exception`
- Structured error codes for categorization
- Log errors via `logger()->error()`

