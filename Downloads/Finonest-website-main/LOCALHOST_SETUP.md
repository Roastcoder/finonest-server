# 🧪 Localhost Testing Setup

## Quick Start

1. **Setup Database**
```bash
mysql -u root -p -e "CREATE DATABASE finonest_db;"
mysql -u root -p finonest_db < backend/schema.sql
```

2. **Start Servers**
```bash
./start-localhost.sh
```

3. **Access Application**
- Frontend: http://localhost:3000
- Backend API: http://localhost:8000

## Test Accounts

**Admin Login:**
- Email: `admin@finonest.com`
- Password: `password`

**Customer Registration:**
- Create new account via registration form

## API Testing

**Test Authentication:**
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@finonest.com","password":"password"}'
```

**Test Protected Endpoint:**
```bash
curl -X GET http://localhost:8000/api/user/me \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

## Stop Servers
```bash
./stop-servers.sh
```

## Troubleshooting

- **MySQL Connection**: Ensure MySQL is running and credentials are correct
- **Port Conflicts**: Change ports in scripts if 3000/8000 are occupied
- **CORS Issues**: Check browser console for cross-origin errors