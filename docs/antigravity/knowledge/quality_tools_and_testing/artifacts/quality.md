# Quality Tools and Testing

The `pokenini-back` project maintains the same extremely high quality standards as `pokenini-api`.

## Static Analysis & Linters

- **PHPStan**: Configured at `level: 9` (strictest). Uses extensions for Symfony and PHPUnit. Command: `make phpstan`.
- **Psalm**: Used for secondary static analysis at a high level with strict typing. Command: `make psalm`.
- **PHP-CS-Fixer**: Enforces coding standards. Commands: `make phpcsfixer` / `make phpcsfixer-fix`.
- **Deptrac**: Enforces architectural boundaries, preventing unwanted dependencies between layers. Command: `make deptrac`.
- **PHPMD**: Detects code smells and complex code blocks. Command: `make phpmd`.
- **Infra Linters**: `hadolint` for Dockerfile, `dotenv-linter` for .env files, `dclint` for docker-compose. Command: `make infra-quality`.

## Testing Strategy

- **PHPUnit**: Used for both Unit and Integration tests. Commands: `make tests-unit` (`tu`), `make tests-integration` (`ti`).
- **Coverage**: The project aims for **100% code coverage**, enforced by the Makefile. Command: `make coverage`.
- **Mutation Testing (Infection)**: The project strictly requires **100% Mutation Score Indicator (MSI)** and **100% Covered MSI**. Command: `make infection`.
- **API Mocking**: Integration tests rely heavily on `moco` to mock the external `pokenini-api` endpoints. The mocks are stored in `tests/resources/moco/Api` and `tests/resources/moco/OAuth`.

## Unified Quality Commands

The main commands to run before pushing code are:
- `make cq` (code-quality): Runs all static analysis and linters.
- `make t` (tests): Runs all PHPUnit tests.
- `make m` (measures): Runs coverage and infection checks.
- `make s` (security): Runs composer audit and symfony security checker.

Workflow standard: `make cq t m s`.
