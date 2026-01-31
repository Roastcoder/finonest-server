# Finonest Backend - Financial Services API

A comprehensive backend API for the Finonest financial services platform built with Node.js, TypeScript, and Express.js.

## Technology Stack

- **Runtime**: Node.js
- **Language**: TypeScript
- **Framework**: Express.js
- **Database**: MongoDB with Mongoose ODM
- **Authentication**: JWT tokens
- **Validation**: Express Validator
- **File Upload**: Multer
- **Environment**: dotenv
- **SMS Service**: SMS integration for notifications

## Features

- **Authentication & Authorization**: JWT-based user authentication with role-based access control
- **Customer Management**: Complete customer lifecycle management
- **Lead Management**: Lead tracking and conversion system
- **Content Management**: Blog and CMS functionality
- **Service Management**: Financial service configuration and management
- **Media Management**: File upload and media handling
- **SMS Integration**: Automated SMS notifications
- **Admin Dashboard**: Comprehensive admin APIs
- **Form Processing**: Dynamic form handling and validation

## Project Structure

```
src/
├── controllers/          # Request handlers
├── middleware/          # Authentication, validation, error handling
├── models/             # Database schemas and models
├── routes/             # API route definitions
├── services/           # Business logic and external integrations
├── utils/              # Utility functions
├── validators/         # Input validation schemas
├── types/              # TypeScript type definitions
├── app.ts              # Express app configuration
└── server.ts           # Server entry point
```

## API Endpoints

### Authentication
- `POST /api/auth/login` - User login
- `POST /api/auth/register` - User registration
- `POST /api/auth/refresh` - Refresh JWT token
- `POST /api/auth/logout` - User logout

### Customer Management
- `GET /api/customers` - Get all customers
- `POST /api/customers` - Create new customer
- `GET /api/customers/:id` - Get customer by ID
- `PUT /api/customers/:id` - Update customer
- `DELETE /api/customers/:id` - Delete customer

### Lead Management
- `GET /api/leads` - Get all leads
- `POST /api/leads` - Create new lead
- `PUT /api/leads/:id` - Update lead status
- `DELETE /api/leads/:id` - Delete lead

### Services
- `GET /api/services` - Get all services
- `POST /api/services` - Create new service
- `PUT /api/services/:id` - Update service
- `DELETE /api/services/:id` - Delete service

### Blog/CMS
- `GET /api/blog/posts` - Get all blog posts
- `POST /api/blog/posts` - Create new post
- `PUT /api/blog/posts/:id` - Update post
- `DELETE /api/blog/posts/:id` - Delete post

### Media
- `POST /api/media/upload` - Upload files
- `GET /api/media/:id` - Get media file
- `DELETE /api/media/:id` - Delete media file

## Installation & Setup

### Prerequisites
- Node.js (v16 or higher)
- MongoDB database
- npm or yarn

### Installation

1. **Clone the repository**:
   ```bash
   git clone https://github.com/Roastcoder/finonest-server.git
   cd finonest-server
   ```

2. **Install dependencies**:
   ```bash
   npm install
   ```

3. **Environment Configuration**:
   Create a `.env` file in the root directory:
   ```env
   # Server Configuration
   PORT=5000
   NODE_ENV=development
   
   # Database
   MONGODB_URI=mongodb://localhost:27017/finonest
   
   # JWT Configuration
   JWT_SECRET=your_jwt_secret_key
   JWT_EXPIRES_IN=7d
   REFRESH_TOKEN_SECRET=your_refresh_token_secret
   REFRESH_TOKEN_EXPIRES_IN=30d
   
   # SMS Configuration
   SMS_API_KEY=your_sms_api_key
   SMS_SENDER_ID=your_sender_id
   
   # File Upload
   UPLOAD_PATH=./uploads
   MAX_FILE_SIZE=5242880
   
   # CORS
   FRONTEND_URL=http://localhost:3000
   ```

4. **Database Setup**:
   ```bash
   # Make sure MongoDB is running
   # Run database seeding (optional)
   npm run seed
   ```

5. **Start the server**:
   ```bash
   # Development mode
   npm run dev
   
   # Production mode
   npm run build
   npm start
   ```

## Scripts

- `npm run dev` - Start development server with hot reload
- `npm run build` - Build TypeScript to JavaScript
- `npm start` - Start production server
- `npm run seed` - Seed database with initial data
- `npm run test` - Run tests
- `npm run lint` - Run ESLint

## Database Models

### User
- Authentication and user management
- Role-based permissions (admin, user, customer)

### Customer
- Customer profile information
- KYC details and documents
- Application history

### Lead
- Lead information and source tracking
- Status management and conversion tracking
- Assignment to sales representatives

### Service
- Financial service definitions
- Eligibility criteria and requirements
- Interest rates and terms

### Blog
- Content management for blog posts
- Categories and tags
- SEO optimization fields

### Media
- File upload and management
- Image optimization and resizing
- Secure file access

## Middleware

### Authentication
- JWT token validation
- Role-based access control
- Session management

### Validation
- Request body validation using Express Validator
- File upload validation
- Data sanitization

### Error Handling
- Centralized error handling
- Custom error responses
- Logging and monitoring

## Security Features

- **JWT Authentication**: Secure token-based authentication
- **Password Hashing**: Bcrypt password encryption
- **Input Validation**: Comprehensive request validation
- **CORS Protection**: Cross-origin request security
- **Rate Limiting**: API rate limiting protection
- **File Upload Security**: Secure file handling and validation

## Deployment

### Docker Deployment
```bash
# Build Docker image
docker build -t finonest-backend .

# Run container
docker run -p 5000:5000 --env-file .env finonest-backend
```

### Production Deployment
1. Set `NODE_ENV=production`
2. Configure production database
3. Set up SSL certificates
4. Configure reverse proxy (Nginx)
5. Set up monitoring and logging

## API Documentation

For detailed API documentation, visit `/api/docs` when the server is running (if Swagger is configured).

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This project is proprietary software. All rights reserved.

## Support

For support and queries, please contact the development team.

---

**Finonest Backend** - Powering Financial Services