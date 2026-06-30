# How to Run the App

## Prerequisites

- Docker Desktop
- Docker Compose

## Start the App

From the project root:

```bash
docker compose up -d --build
```

This starts:

- PHP/Apache app
- MySQL database
- phpMyAdmin

## Open the App

- Web app: http://localhost/
- phpMyAdmin: http://localhost:8080/

phpMyAdmin credentials:

- Server: `mysql`
- Username: `root`
- Password: `root`

## Database Settings

The app uses these database settings in Docker:

- Host: `mysql`
- Port: `3306`
- Database: `khmer245_db`
- User: `app_user`
- Password: `secret`

The MySQL port exposed on your computer is `3307`.

## Useful Commands

View running containers:

```bash
docker compose ps
```

View logs:

```bash
docker compose logs -f
```

Stop the app:

```bash
docker compose down
```

Restart after changing files:

```bash
docker compose up -d --build
```

## If the Database Still Fails

If you previously ran the app with the old database settings, the Docker volume may still contain old permissions or database state.

To fully reset the database and re-import the bundled SQL file:

```bash
docker compose down -v
docker compose up -d --build
```

This deletes the local MySQL Docker volume, so any database changes made after import will be lost.
