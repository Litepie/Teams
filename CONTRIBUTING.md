# Contributing to Lavalite Teams

Thank you for considering contributing to Lavalite Teams! We welcome contributions from everyone and are grateful for even the smallest of improvements.

## 🤝 Code of Conduct

This project and everyone participating in it is governed by our [Code of Conduct](CODE_OF_CONDUCT.md). By participating, you are expected to uphold this code.

## 🐛 Bug Reports

If you discover a bug, please create an issue on GitHub with the following information:

### Before Submitting a Bug Report

- Check the existing issues to avoid duplicates
- Update to the latest version to see if the issue persists
- Test with a minimal reproduction case

### Bug Report Template

```markdown
**Bug Description**
A clear and concise description of what the bug is.

**To Reproduce**
Steps to reproduce the behavior:
1. Go to '...'
2. Click on '....'
3. Scroll down to '....'
4. See error

**Expected Behavior**
A clear and concise description of what you expected to happen.

**Environment**
- PHP Version: [e.g. 8.2]
- Laravel Version: [e.g. 12.0]
- Package Version: [e.g. 2.0.0]
- Database: [e.g. MySQL 8.0]

**Additional Context**
Add any other context about the problem here, including error messages, logs, etc.
```

## 💡 Feature Requests

We love new ideas! Before submitting a feature request:

1. Check existing issues and discussions
2. Consider if it fits the project's scope
3. Think about backward compatibility

### Feature Request Template

```markdown
**Feature Description**
A clear and concise description of the feature you'd like to see.

**Problem it Solves**
Describe the problem this feature would solve.

**Proposed Solution**
Describe how you envision this feature working.

**Alternatives Considered**
Alternative solutions or workarounds you've considered.

**Additional Context**
Screenshots, mockups, or examples that help explain the feature.
```

## 🔧 Pull Requests

### Development Process

1. **Fork the Repository**
   ```bash
   git clone https://github.com/your-username/teams.git
   cd teams
   ```

2. **Create a Feature Branch**
   ```bash
   git checkout -b feature/your-feature-name
   # or
   git checkout -b fix/issue-number
   ```

3. **Install Dependencies**
   ```bash
   composer install
   npm install
   ```

4. **Set Up Testing Environment**
   ```bash
   cp .env.example .env.testing
   php artisan key:generate --env=testing
   ```

5. **Make Your Changes**
   - Write tests for new functionality
   - Update documentation if needed
   - Follow coding standards (PSR-12)

6. **Run Tests**
   ```bash
   composer test
   composer test:coverage
   composer analyse
   ```

7. **Commit Your Changes**
   ```bash
   git add .
   git commit -m "feat: add new team analytics feature"
   ```

8. **Push and Create PR**
   ```bash
   git push origin feature/your-feature-name
   ```

### Pull Request Guidelines

- **Clear Title**: Use descriptive titles following [Conventional Commits](https://conventionalcommits.org/)
- **Detailed Description**: Explain what changes you made and why
- **Link Issues**: Reference any related issues
- **Tests**: Include tests for new functionality
- **Documentation**: Update docs if your changes affect the API

### Conventional Commits

We use conventional commits for clear history:

- `feat:` - New features
- `fix:` - Bug fixes
- `docs:` - Documentation changes
- `test:` - Adding or updating tests
- `refactor:` - Code changes that neither fix bugs nor add features
- `perf:` - Performance improvements
- `chore:` - Maintenance tasks

Examples:
```
feat: add team analytics dashboard
fix: resolve member invitation email bug
docs: update installation instructions
test: add integration tests for team workflows
```

## 🧪 Testing

### Running Tests

```bash
# Run all tests
composer test

# Run with coverage
composer test:coverage

# Run specific test file
./vendor/bin/pest tests/Feature/TeamManagementTest.php

# Run specific test method
./vendor/bin/pest --filter="it can create team"
```

### Writing Tests

We use [Pest](https://pestphp.com/) for testing. Here's a sample test:

```php
<?php

use Litepie\Teams\Models\Team;
use Litepie\Teams\Actions\CreateTeamAction;

it('can create a team with valid data', function () {
    $user = User::factory()->create();
    
    $result = CreateTeamAction::execute(null, [
        'name' => 'Test Team',
        'description' => 'A test team',
        'type' => 'development',
    ], $user);
    
    expect($result)
        ->toBeInstanceOf(Team::class)
        ->name->toBe('Test Team')
        ->owner_id->toBe($user->id);
});

it('validates required fields', function () {
    $user = User::factory()->create();
    
    expect(fn() => CreateTeamAction::execute(null, [], $user))
        ->toThrow(ValidationException::class);
});
```

### Test Categories

- **Unit Tests** (`tests/Unit/`): Test individual classes and methods
- **Feature Tests** (`tests/Feature/`): Test complete features and workflows
- **Integration Tests** (`tests/Integration/`): Test package integrations

## 📝 Documentation

### Code Documentation

- Use PHP DocBlocks for all public methods
- Include parameter types and return types
- Explain complex logic with inline comments

```php
/**
 * Create a new team with the specified configuration.
 *
 * @param array $data The team data including name, description, and settings
 * @param User $owner The user who will own this team
 * @return Team The newly created team instance
 * @throws ValidationException When validation fails
 * @throws TeamLimitExceededException When user has reached team limit
 */
public function createTeam(array $data, User $owner): Team
{
    // Implementation
}
```

### README Updates

When adding new features, update the README.md with:
- Usage examples
- Configuration options
- API documentation

## 🎨 Code Style

### PHP Standards

We follow PSR-12 coding standards:

```bash
# Fix code style automatically
composer format

# Check code style
composer check-style
```

### Static Analysis

We use PHPStan for static analysis:

```bash
# Run static analysis
composer analyse

# Run with specific level
./vendor/bin/phpstan analyse --level=8
```

### Type Coverage

Maintain high type coverage:

```bash
# Check type coverage
composer test:types
```

## 🚀 Release Process

### Versioning

We follow [Semantic Versioning](https://semver.org/):

- **MAJOR** (2.0.0): Breaking changes
- **MINOR** (2.1.0): New features, backward compatible
- **PATCH** (2.0.1): Bug fixes, backward compatible

### Changelog

Update `CHANGELOG.md` with:
- New features
- Bug fixes
- Breaking changes
- Deprecations

## 🏷️ Issue Labels

We use these labels to organize issues:

- `bug` - Something isn't working
- `enhancement` - New feature or request
- `documentation` - Improvements or additions to documentation
- `good first issue` - Good for newcomers
- `help wanted` - Extra attention is needed
- `question` - Further information is requested
- `wontfix` - This will not be worked on

## 🎯 Development Guidelines

### Architecture Principles

1. **Single Responsibility**: Each class should have one reason to change
2. **Dependency Injection**: Use constructor injection for dependencies
3. **Action Pattern**: Business logic should be in Action classes
4. **Event-Driven**: Use events for loose coupling
5. **Multi-Tenant**: Everything should be tenant-aware

### Best Practices

- Write self-documenting code
- Keep methods small and focused
- Use meaningful variable and method names
- Avoid deep nesting
- Handle edge cases gracefully

### Performance Considerations

- Use database indexes appropriately
- Implement caching where beneficial
- Avoid N+1 queries
- Use eager loading for relationships
- Consider pagination for large datasets

## 📬 Questions?

If you have questions about contributing:

1. Check existing documentation
2. Search closed issues for similar questions
3. Open a discussion on GitHub
4. Join our community chat
5. Email us at dev@renfos.com

## 🙏 Recognition

Contributors will be:

- Added to the contributors list
- Mentioned in release notes for significant contributions
- Invited to join our contributors team (for regular contributors)

Thank you for making Lavalite Teams better! 🚀
