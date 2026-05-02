# Data Management

Unlike `pokenini-api` which persists data to PostgreSQL, `pokenini-back` is stateless regarding domain data.

## Data Fetching

- **Guzzle HTTP Client**: `pokenini-back` relies heavily on Guzzle to make requests to the external `pokenini-api` server.
- **Services**: The `src/Service` layer acts as the repository layer, effectively fetching and transforming JSON payloads into internal DTOs.

## Caching Strategy

Because the application makes frequent network calls to the core API, caching is a fundamental part of the data management strategy.
- **Tools**: APCu (for local fast cache) and Redis (for distributed cache).
- **Cache Classes**: Found in `src/Cache`, they decorate or wrap the API calls to avoid redundant HTTP requests and reduce latency for the end users.

## Mocking Data for Tests

To maintain robust tests without depending on the actual API being up, the project uses a mock server.
- **Moco**: Moco is used to mock the API responses.
- **Mock Files**: Mock configurations and JSON responses from `pokenini-api` are stored in `tests/resources/moco/Api` and `tests/resources/moco/OAuth`.
- **Updating Mocks**: The `README.md` provides `curl` commands to re-download the latest payloads from the API and update the local mocks.
