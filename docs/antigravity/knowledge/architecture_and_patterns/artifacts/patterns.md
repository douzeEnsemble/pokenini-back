# Architecture and Patterns

The project follows a modern Symfony architecture with a strong emphasis on strict typing and functional separation, mirroring the API but tailored for an API Gateway / BFF role.

## Layered Structure

1.  **Controllers** (`src/Controller`): Handle HTTP requests, manage security checks, map request data to DTOs, and return responses (usually JSON or Twig views).
2.  **Services** (`src/Service`): External API communication logic using Guzzle HTTP client. They encapsulate the calls to `pokenini-api`.
3.  **DTOs** (`src/DTO`): Used as rigid data structures to parse incoming request data or external API responses.
4.  **Cache** (`src/Cache`): Important layer since the app doesn't have a DB. Wraps API calls to cache responses efficiently.
5.  **Validators** (`src/Validator`): Custom validation rules.
6.  **Security** (`src/Security`): OAuth2 authenticators and user providers.

## Coding Standards

- **Strict Typing**: Every PHP file uses `declare(strict_types=1);`.
- **PHP 8.5.4+ Features**: The codebase extensively uses modern features.
- **Service Injection**: Dependency injection is heavily used through constructors.

## Key Patterns

- **No Doctrine ORM**: Because this is a frontend/BFF, it completely delegates data persistence and querying to `pokenini-api`.
- **DTO-based Validation**: Similar to the API, responses and requests are immediately serialized/deserialized into DTOs.
