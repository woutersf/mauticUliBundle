# Mautic Unique Login Links (ULI) Bundle

This plugin provides the ability to generate secure one-time login links for Mautic users via console command.

## Features

- 🔐 **Secure One-Time Links**: Generate cryptographically secure login URLs
- ⏰ **Auto-Expiration**: Links expire after 24 hours (configurable)
- 🗑️ **Auto-Cleanup**: Used links are automatically deleted
- 📋 **Comprehensive Logging**: All login attempts logged for security auditing
- 🛡️ **Security Built-in**: Protection against replay attacks and expired tokens
- 💻 **CLI-Based**: Easy integration with automation and scripts

## Usage

### Generate a unique login link

```bash
php bin/console mautic:uli {user_id}
```

Example:
```bash
php bin/console mautic:uli 1
```

This will generate a URL like:
```
https://yourmautic.com/s/unique_login?hash=abc123...
```

### Access the login link

Users can access the generated URL in their browser to be automatically logged in. The link:
- Expires after 24 hours
- Is automatically deleted after use
- Logs all access attempts (successful and failed)

## Database Schema

The plugin creates a table `plugin_uli_unique_logins` with the following structure:

- `id` - Primary key
- `hash` - Unique 64-character hash
- `user_id` - Foreign key to users table
- `ttl` - Time-to-live (expiration datetime)
- `date_created` - Creation timestamp

## Security Features

- Cryptographically secure random hash generation
- Automatic cleanup of expired tokens
- Comprehensive logging of all login attempts
- User account status validation
- Protection against replay attacks (one-time use)

## Installation

1. Copy the plugin to `plugins/MauticUliBundle/`
2. Run database migrations: `php bin/console doctrine:migrations:migrate`
3. Clear cache: `php bin/console cache:clear`

## Configuration

The plugin supports the following configuration parameters:
- `uli_token_lifetime` - Token lifetime in hours (default: 24)