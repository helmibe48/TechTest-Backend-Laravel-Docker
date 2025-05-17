# FrankenPHP and Laravel Octane with Docker + Laravel 11

This repo is a docker boilerplate to use for Laravel projects. Containers included in this docker:

1. [Laravel 11 & 12](https://laravel.com/docs/)
2. [FrankenPHP](https://frankenphp.dev/docs/docker/)
3. MySQL
4. Redis
5. Supervisor
6. [Octane](https://laravel.com/docs/octane)
7. Minio for S3
8. MailPit

## Application Setup

Copy the .env.example file to .env:

```bash
# Linux
$ cp .env.example .env
# OR
# Windows
$ copy .env.example .env
```

Edit the `.env` file to configure your application settings. At a minimum, you should set the following variables:

- `APP_NAME`: The name of your application.
- `APP_ENV`: The environment your application is running in (e.g., local, production).
- `APP_KEY`: The application key (will be generated in the next step).
- `APP_DEBUG`: Set to `true` for debugging.
- `APP_URL`: The URL of your application.
- `DB_CONNECTION`: The database connection (e.g., mysql).
- `DB_HOST`: The database host.
- `DB_PORT`: The database port.
- `DB_DATABASE`: The database name.
- `DB_USERNAME`: The database username.
- `DB_PASSWORD`: The database password.

**Edit docker related setting according to your preferences.**

Run composer to install the required packages:

```bash
# install required packages
$ composer install
```

Generate a new application key:

```bash
# app key setup
$ php artisan key:generate
```

## Usage

Build the Docker images:

```bash
# build docker images
$ docker compose build
```

Run the containers:

```bash
# Run containers
$ docker compose up -d
```

To stop the containers, run:

```bash
# Stop containers
$ docker compose down
```
