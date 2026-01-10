import express from 'express';
import morgan from 'morgan';
import helmet from 'helmet';
import cors from 'cors';
import publicRoutes from './routes/public';
import adminRoutes from './routes/admin';
import authRoutes from './routes/auth';
import customerAuthRoutes from './routes/customer-auth';
import customerRoutes from './routes/customer';
import systemRoutes from './routes/system';
import cookieParser from 'cookie-parser';
import mediaRoutes from './routes/media';
import path from 'path';
import { attachUser } from './middleware/auth';
import { attachCustomer } from './middleware/customer-auth';
import { errorHandler, notFoundHandler } from './middleware/errorHandler';

const app = express();

// Security & CORS
app.use(helmet({
  crossOriginResourcePolicy: { policy: "cross-origin" }
}));
app.use(cors({ 
  origin: true, // Allow all origins for now
  credentials: true 
}));

// Body parsing
app.use(express.json({ limit: '5mb' }));
app.use(express.urlencoded({ extended: true }));

// Logging
app.use(morgan(process.env.NODE_ENV === 'production' ? 'combined' : 'dev'));

// Cookie parser
app.use(cookieParser());

// Trust proxy for production
if (process.env.NODE_ENV === 'production') {
  app.set('trust proxy', 1);
}

// System routes (health, status, root)
app.use('/', systemRoutes);

// Auth middleware
app.use(attachUser);
app.use(attachCustomer);

// API routes
app.use('/api', publicRoutes);
app.use('/api/auth', authRoutes);
app.use('/api/customer/auth', customerAuthRoutes);
app.use('/api/customer', customerRoutes);
app.use('/api', mediaRoutes);
app.use('/api/admin', adminRoutes);

// Static assets
app.use('/assets/uploads', express.static(path.join(process.cwd(), 'public', 'assets', 'uploads')));

// 404 handler
app.use(notFoundHandler);

// Global error handler
app.use(errorHandler);

export default app;
