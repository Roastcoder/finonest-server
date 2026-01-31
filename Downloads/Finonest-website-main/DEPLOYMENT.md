# Finonest Full-Stack Deployment Guide

## Backend Setup (PHP)

### 1. Server Requirements
- PHP 8.0+
- MySQL 5.7+
- Apache/Nginx with mod_rewrite
- SSL Certificate

### 2. Database Setup
```bash
mysql -u root -p < backend/schema.sql
```

### 3. Apache Configuration
```apache
<VirtualHost *:443>
    ServerName api.finonest.com
    DocumentRoot /var/www/finonest/backend
    
    SSLEngine on
    SSLCertificateFile /path/to/certificate.crt
    SSLCertificateKeyFile /path/to/private.key
    
    <Directory /var/www/finonest/backend>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### 4. Environment Variables
Create `.env` in backend root:
```
DB_HOST=localhost
DB_NAME=finonest_db
DB_USER=finonest_user
DB_PASS=secure_password
JWT_SECRET=your_jwt_secret_key
```

## Frontend Setup (React)

### 1. Build for Production
```bash
npm run build
```

### 2. Nginx Configuration
```nginx
server {
    listen 443 ssl;
    server_name finonest.com;
    
    ssl_certificate /path/to/certificate.crt;
    ssl_certificate_key /path/to/private.key;
    
    root /var/www/finonest/dist;
    index index.html;
    
    location / {
        try_files $uri $uri/ /index.html;
    }
    
    location /api {
        proxy_pass https://api.finonest.com;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }
}
```

## Security Checklist

- [x] JWT token expiration
- [x] Password hashing with bcrypt
- [x] SQL injection prevention
- [x] CORS configuration
- [x] HTTPS enforcement
- [x] Input validation
- [x] Rate limiting
- [x] Security headers

## Monitoring & Logs

### Error Logging
```php
error_log("Error: " . $message, 3, "/var/log/finonest/error.log");
```

### Access Logs
Monitor API usage and authentication attempts.

## Backup Strategy

### Database Backup
```bash
mysqldump -u root -p finonest_db > backup_$(date +%Y%m%d).sql
```

### File Backup
Regular backup of application files and user uploads.