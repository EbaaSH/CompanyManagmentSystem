# Docker Setup Guide

This project is now containerized with Docker using PHP 8.3, Apache, MySQL 8.0, and Node.js.

## Prerequisites

- [Docker Desktop](https://www.docker.com/products/docker-desktop) (includes Docker and Docker Compose)
- Windows, macOS, or Linux

## Quick Start

### 1. Build and Run Containers

```bash
docker-compose up -d
```

This will:

- Build the PHP 8.3 Apache image
- Start PHP/Apache on port 8000
- Start MySQL on port 3306
- Start Node.js for frontend on port 5173

### 2. Install Dependencies

```bash
docker-compose exec app composer install
docker-compose exec app npm install
```

### 3. Setup Application

```bash
docker-compose exec app cp .env.example .env
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan migrate
```

### 4. Build Frontend Assets

```bash
docker-compose exec node npm run build
```

Or for development with watch mode:

```bash
docker-compose exec node npm run dev
```

## Accessing Your Application

- **Web Application**: http://localhost:8000
- **Vite Dev Server**: http://localhost:5173 (if running in dev mode)
- **MySQL**: localhost:3306
    - User: `company_user`
    - Password: `company_password`
    - Database: `company_mgmt`

## Useful Commands

### View Logs

```bash
# All services
docker-compose logs -f

# Specific service
docker-compose logs -f app
docker-compose logs -f mysql
```

### Execute Commands in Container

```bash
# Run PHP commands
docker-compose exec app php artisan tinker
docker-compose exec app php artisan migrate:fresh --seed

# Run composer commands
docker-compose exec app composer require package-name

# Run artisan commands
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
```

### Stop Containers

```bash
# Stop all
docker-compose down

# Stop and remove volumes
docker-compose down -v
```

### Rebuild After Dockerfile Changes

```bash
docker-compose up -d --build
```

## Environment Variables

Edit `docker-compose.yml` to customize:

- `DB_DATABASE` - MySQL database name
- `DB_USERNAME` - MySQL username
- `DB_PASSWORD` - MySQL password
- `APP_DEBUG` - Laravel debug mode
- `APP_ENV` - Laravel environment (local, production, etc.)

## Database Backup

```bash
docker-compose exec mysql mysqldump -u company_user -pcompany_password company_mgmt > backup.sql
```

## Database Restore

```bash
docker-compose exec -T mysql mysql -u company_user -pcompany_password company_mgmt < backup.sql
```

## Troubleshooting

### Port Already in Use

Change ports in `docker-compose.yml`:

```yaml
ports:
    - "8001:80" # Changed from 8000:80
```

### Permission Issues

```bash
docker-compose exec app chown -R www-data:www-data /var/www/html
```

### Clear Cache

```bash
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
```

## Production Deployment

For production:

1. Change `APP_DEBUG=false`
2. Change `APP_ENV=production`
3. Use environment-specific `.env` file
4. Set strong database passwords
5. Use a reverse proxy (nginx/traefik)
6. Configure proper logging and monitoring

## Support

If you encounter issues:

1. Check Docker logs: `docker-compose logs -f`
2. Verify Docker installation: `docker --version`
3. Ensure ports 8000, 3306, 5173 are available
4. Clear Docker cache: `docker system prune`
