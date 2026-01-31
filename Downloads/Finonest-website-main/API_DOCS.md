# Finonest API Documentation

## Base URL
```
https://api.finonest.com
```

## Authentication
All protected endpoints require JWT token in Authorization header:
```
Authorization: Bearer <jwt_token>
```

## Endpoints

### Authentication

#### POST /api/auth/register
Register a new user account.

**Request Body:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "securepassword"
}
```

**Response:**
```json
{
  "success": true,
  "token": "jwt_token_here",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "role": "customer"
  }
}
```

#### POST /api/auth/login
Authenticate user and get JWT token.

**Request Body:**
```json
{
  "email": "john@example.com",
  "password": "securepassword"
}
```

**Response:**
```json
{
  "success": true,
  "token": "jwt_token_here",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "role": "customer"
  }
}
```

### User Management

#### GET /api/user/me
Get current user profile (requires authentication).

**Response:**
```json
{
  "success": true,
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "role": "customer",
    "created_at": "2024-01-01 00:00:00"
  }
}
```

### Form Submissions

#### POST /api/forms/submit
Submit a loan application (requires customer authentication).

**Request Body:**
```json
{
  "form_data": {
    "loanType": "personal",
    "amount": "50000",
    "income": "75000",
    "employment": "salaried",
    "purpose": "Home renovation"
  }
}
```

**Response:**
```json
{
  "success": true,
  "application_id": 123,
  "message": "Application submitted successfully"
}
```

#### GET /api/forms/mine
Get user's applications (requires customer authentication).

**Query Parameters:**
- `page` (optional): Page number (default: 1)
- `limit` (optional): Items per page (default: 10)

**Response:**
```json
{
  "success": true,
  "applications": [
    {
      "id": 123,
      "form_data": {...},
      "status": "pending",
      "created_at": "2024-01-01 00:00:00"
    }
  ],
  "page": 1,
  "limit": 10
}
```

### Admin Operations

#### GET /api/admin/forms
Get all applications (requires admin authentication).

**Query Parameters:**
- `page` (optional): Page number (default: 1)
- `limit` (optional): Items per page (default: 10)
- `status` (optional): Filter by status (pending/approved/rejected)

**Response:**
```json
{
  "success": true,
  "applications": [
    {
      "id": 123,
      "user_name": "John Doe",
      "user_email": "john@example.com",
      "form_data": {...},
      "status": "pending",
      "created_at": "2024-01-01 00:00:00"
    }
  ],
  "page": 1,
  "limit": 10
}
```

#### PUT /api/admin/forms/{id}
Update application status (requires admin authentication).

**Request Body:**
```json
{
  "status": "approved"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Status updated successfully"
}
```

## Error Responses

All endpoints return error responses in this format:
```json
{
  "error": "Error message description"
}
```

## HTTP Status Codes

- `200` - Success
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `409` - Conflict (e.g., email already exists)
- `500` - Internal Server Error