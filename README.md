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

### Option 1: Composer (Recommended)

1. Add the plugin to your Mautic installation:
   ```bash
   composer require mautic/uli-bundle
   ```

2. Run database migrations:
   ```bash
   php bin/console doctrine:migrations:migrate
   ```

3. Clear the Mautic cache:
   ```bash
   php bin/console cache:clear
   ```

4. Navigate to Mautic Settings → Plugins and verify installation

### Option 2: Manual Installation

1. Clone or download this repository into your Mautic plugins directory:
   ```bash
   cd /path/to/mautic/plugins
   git clone <repository-url> MauticUliBundle
   ```

2. Run database migrations:
   ```bash
   php bin/console doctrine:migrations:migrate
   ```

3. Clear the Mautic cache:
   ```bash
   php bin/console cache:clear
   ```

4. Navigate to Mautic Settings → Plugins and verify installation

### Option 3: Private Repository

If you're hosting this plugin in a private repository, add it to your `composer.json`:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/your-username/mautic-uli-bundle.git"
        }
    ],
    "require": {
        "mautic/uli-bundle": "^1.0"
    }
}
```

Then run:
```bash
composer update mautic/uli-bundle
php bin/console doctrine:migrations:migrate
php bin/console cache:clear
```

## Configuration

The plugin supports the following configuration parameters:
- `uli_token_lifetime` - Token lifetime in hours (default: 24)

You can configure this in your `app/config/local.php`:

```php
return [
    'parameters' => [
        'uli_token_lifetime' => 48, // 48 hours
    ],
];
```

## Use Cases

- **Password Recovery**: Generate secure login links for users who forgot their password
- **Customer Support**: Provide temporary access to user accounts for support purposes
- **Automation**: Integrate with scripts to provide secure access after account creation
- **Emergency Access**: Quick access for administrators without knowing passwords
- **Testing**: Generate login links for testing purposes without managing passwords

## Requirements

- Mautic 4.x or higher
- PHP 7.4 or higher
- Database with migration support

## Publishing to Packagist

To make this plugin available via Composer for everyone:

1. **Push to GitHub** (or GitLab/Bitbucket):
   ```bash
   git init
   git add .
   git commit -m "Initial release v1.0.0"
   git tag v1.0.0
   git remote add origin https://github.com/your-username/mautic-uli-bundle.git
   git push -u origin main
   git push --tags
   ```

2. **Submit to Packagist**:
   - Go to https://packagist.org
   - Sign in with your GitHub account
   - Click "Submit"
   - Enter your repository URL: `https://github.com/your-username/mautic-uli-bundle`
   - Packagist will automatically track new releases via your Git tags

3. **Auto-Update Hook** (Optional):
   - In your GitHub repository settings, add Packagist webhook for automatic updates
   - Settings → Webhooks → Add webhook
   - Payload URL: `https://packagist.org/api/github?username=YOUR_USERNAME`

## Version Tagging

When releasing new versions:
```bash
# Update composer.json version if needed
git add .
git commit -m "Release v1.1.0"
git tag v1.1.0
git push && git push --tags
```

Packagist will automatically detect the new tag and update the package.

## Author

Mautic Community / Frederik Wouters

## License

GPL-3.0-or-later

## Support

For issues, questions, or contributions, please open an issue in the repository.

## Changelog

### Version 1.0.0
- Initial release
- Secure one-time login link generation via CLI
- 24-hour token expiration (configurable)
- Automatic cleanup of used/expired tokens
- Comprehensive security logging
- Database migrations
- Composer support