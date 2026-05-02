# Project Overview: pokenini-back

The `pokenini-back` is a backend application built with **Symfony 8.0** and **PHP 8.5.4+**. It acts as a Backend-for-Frontend (BFF) and API Gateway for the Pokémon Living/Alternate/Gender Extended Dex ecosystem.

Unlike the API project, it does not use a local database (no Doctrine ORM). Instead, it communicates dynamically with the `pokenini-api` to fetch and submit data.

## Technology Stack

- **PHP**: 8.5.4+
- **Framework**: Symfony 8.0
- **HTTP Client**: Guzzle HTTP (`guzzlehttp/guzzle`)
- **Authentication**: OAuth2 Clients (`league/oauth2-google`, `wohali/oauth2-discord-new`) via `knpuniversity/oauth2-client-bundle`.
- **Caching**: APCu and Redis (for optimizing external API calls).

## Running the Application

The project uses Docker for its local environment and provides a comprehensive `Makefile`.

### Common Commands
- `make start`: Installs dependencies, builds the docker image, starts the containers, and clears cache.
- `make stop`: Stops the docker containers.
- `make destruct`: Destroys the containers and volumes.
- `make sh` / `make bash`: Opens a shell inside the PHP container.

### Local Development Authentication
To facilitate local development without configuring full OAuth2 pipelines, the project provides fake authentication endpoints:
- Admin: `http://localhost/fr/connect/f/c?t=admin`
- Collector: `http://localhost/fr/connect/f/c?t=collector`
- Trainer: `http://localhost/fr/connect/f/c?t=trainer`
