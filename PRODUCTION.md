# Environment Configuration

## Required Environment Variables

### Core Settings
```bash
NODE_ENV=production
PORT=4000
```

### Database
```bash
MONGO_URI=mongodb://username:password@host:port/database
```

### Security
```bash
JWT_SECRET=your-super-secure-jwt-secret-key-here
JWT_REFRESH_SECRET=your-super-secure-refresh-secret-key-here
```

## Production Checklist

### Security
- [ ] Strong JWT secrets (32+ characters)
- [ ] CORS configured for production domains
- [ ] Helmet security headers enabled
- [ ] No sensitive data in logs

### Performance
- [ ] MongoDB connection pooling configured
- [ ] Request size limits set (5MB)
- [ ] Proper error handling without stack traces

### Monitoring
- [ ] Health endpoint accessible: `/health`
- [ ] Status endpoint accessible: `/api/status`
- [ ] Structured logging enabled
- [ ] Graceful shutdown implemented

### Deployment
- [ ] NODE_ENV=production
- [ ] Process manager (PM2/Docker)
- [ ] Load balancer ready
- [ ] SSL/TLS terminated at proxy level