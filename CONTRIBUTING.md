# Contributing

Thank you for considering contributing to Laravel Asterisk ARI! We welcome contributions from the community and are grateful for any help you can provide.

## Code of Conduct

By participating in this project, you agree to maintain a respectful and inclusive environment for everyone. Be kind, constructive, and professional in all interactions.

## How Can I Contribute?

### Reporting Bugs

Before creating a bug report, please check if the issue has already been reported. When creating a bug report, include as much detail as possible:

- **PHP and Laravel version** you are using
- **Asterisk version** and ARI configuration
- **Steps to reproduce** the issue
- **Expected behavior** vs **actual behavior**
- **Error messages** or stack traces (if applicable)
- **Code samples** that demonstrate the issue

### Suggesting Features

Feature requests are welcome. Please open an issue and describe:

- The problem you are trying to solve
- How the feature would work
- Why this feature would be useful to other users

### Pull Requests

1. Fork the repository
2. Create your feature branch from `main`:
   ```bash
   git checkout -b feature/my-new-feature
   ```
3. Make your changes
4. Write or update tests as needed
5. Ensure all tests pass
6. Commit your changes
7. Push to your fork
8. Open a Pull Request against `main`

## Development Setup

### Requirements

- PHP ^8.3
- Composer

### Installation

```bash
git clone git@github.com:voipforall/laravel-asterisk-ari.git
cd laravel-asterisk-ari
composer install
```

### Running Tests

```bash
composer test
```

Make sure all tests pass before submitting a pull request.

## Coding Standards

### Style Guide

This project follows the [PSR-12](https://www.php-fig.org/psr/psr-12/) coding standard and the [Laravel coding style](https://laravel.com/docs/contributions#coding-style).

Key points:

- Use **strict types** and type hints wherever possible
- Use **readonly** properties and constructor promotion
- Prefer **named arguments** for methods with multiple parameters
- Use **early returns** to reduce nesting
- Keep methods short and focused on a single responsibility

### Naming Conventions

- Classes: `PascalCase`
- Methods and variables: `camelCase`
- Constants: `UPPER_SNAKE_CASE`
- Config keys: `snake_case`

### Imports

- Always use explicit `use` statements (no inline FQCNs)
- Sort imports alphabetically
- Group imports: PHP native classes first, then vendor, then project

### Tests

- Write tests using [Pest](https://pestphp.com/)
- Use descriptive test names: `it('originates a call with all parameters')`
- One assertion concept per test
- Use `beforeEach` for shared setup
- Mock external dependencies using Mockery
- Place unit tests in `tests/Unit/` and feature tests in `tests/Feature/`

## Commit Messages

Follow the [Conventional Commits](https://www.conventionalcommits.org/) specification:

```
<type>: <short description>

<optional body>
```

Types:

| Type | Description |
|---|---|
| `feat` | A new feature |
| `fix` | A bug fix |
| `docs` | Documentation changes |
| `test` | Adding or updating tests |
| `refactor` | Code changes that neither fix a bug nor add a feature |
| `chore` | Maintenance tasks (dependencies, CI, etc.) |

Examples:

```
feat: add playback resource for channel audio
fix: handle empty response on channel hangup
docs: add originate example with named arguments
test: add coverage for bridge channel removal
```

## Pull Request Guidelines

- **Keep PRs small and focused.** One feature or fix per PR.
- **Write a clear description** of what the PR does and why.
- **Reference related issues** using `Closes #123` or `Fixes #456`.
- **Update documentation** if your change affects the public API.
- **Add tests** for new features and bug fixes.
- **Do not include unrelated changes** (formatting, refactoring) in the same PR.

## Branch Naming

Use descriptive branch names with a prefix:

- `feature/add-playback-resource`
- `fix/websocket-reconnection-delay`
- `docs/update-bridge-examples`
- `test/add-recording-coverage`

## Questions?

If you have questions about contributing, feel free to open an issue or reach out to the maintainers.

Thank you for helping make Laravel Asterisk ARI better!
