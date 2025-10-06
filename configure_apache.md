# Configure Apache for Laravel Application

## Issue Summary
The Laravel built-in development server (`php artisan serve`) crashes when handling the biometric endpoint due to connection handling limitations. The solution is to use Apache instead.

## Apache Configuration Steps

### 1. Create Apache Virtual Host

Create a new file: `C:\xampp\apache\conf\extra\httpd-vhosts.conf` (or edit existing)

```apache
<VirtualHost *:80>
    DocumentRoot "C:/xampp/htdocs/PNS-Dhampur/public"
    ServerName pns-dhampur.local
    ServerAlias www.pns-dhampur.local
    
    <Directory "C:/xampp/htdocs/PNS-Dhampur/public">
        AllowOverride All
        Require all granted
        DirectoryIndex index.php
        
        # Enable URL rewriting
        RewriteEngine On
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteRule ^(.*)$ index.php [QSA,L]
    </Directory>
    
    # Error and access logs
    ErrorLog "logs/pns-dhampur-error.log"
    CustomLog "logs/pns-dhampur-access.log" common
</VirtualHost>
```

### 2. Update Windows Hosts File

Edit `C:\Windows\System32\drivers\etc\hosts` (as Administrator):

```
127.0.0.1 pns-dhampur.local
127.0.0.1 www.pns-dhampur.local
```

### 3. Enable Apache Modules

In `C:\xampp\apache\conf\httpd.conf`, ensure these modules are enabled:

```apache
LoadModule rewrite_module modules/mod_rewrite.so
LoadModule vhost_alias_module modules/mod_vhost_alias.so
```

### 4. Include Virtual Hosts

In `C:\xampp\apache\conf\httpd.conf`, uncomment:

```apache
Include conf/extra/httpd-vhosts.conf
```

### 5. Set Laravel Permissions

Ensure the following directories are writable:
- `storage/`
- `bootstrap/cache/`

### 6. Environment Configuration

Update `.env` file:

```env
APP_URL=http://pns-dhampur.local
```

## Testing Steps

1. **Start XAMPP Apache** (not the Laravel server)
2. **Access the application**: `http://pns-dhampur.local`
3. **Test the biometric endpoint**: `http://pns-dhampur.local/api/external/biometric/devices`

## Benefits of Apache over Laravel Built-in Server

- ✅ **Stable connection handling** - No connection resets
- ✅ **Better performance** - Optimized for web serving
- ✅ **Production-like environment** - More realistic testing
- ✅ **Concurrent request support** - Multiple users can access simultaneously
- ✅ **Proper error handling** - Better debugging capabilities

## Troubleshooting

### Common Issues:

1. **403 Forbidden**: Check directory permissions
2. **500 Internal Server Error**: Check Laravel logs in `storage/logs/`
3. **Module not found**: Ensure mod_rewrite is enabled
4. **Virtual host not working**: Restart Apache after configuration changes

### Useful Commands:

```bash
# Check Apache configuration
httpd -t

# Restart Apache (from XAMPP Control Panel)
# Or via command line:
net stop apache2.4
net start apache2.4
```

## Final Test

Once configured, test the biometric endpoint:

```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Accept: application/json" \
     http://pns-dhampur.local/api/external/biometric/devices
```

This should return a successful response without connection resets.