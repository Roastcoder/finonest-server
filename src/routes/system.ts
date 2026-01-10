import { Router, Request, Response } from 'express';
import mongoose from 'mongoose';

const router = Router();

// Root route - Human readable
router.get('/', (req: Request, res: Response) => {
  res.send('🚀 Finonest API is running successfully');
});

// Health check endpoint
router.get('/health', (req: Request, res: Response) => {
  const dbStatus = mongoose.connection.readyState === 1 ? 'connected' : 'disconnected';
  
  res.json({
    status: 'healthy',
    database: dbStatus,
    timestamp: new Date().toISOString()
  });
});

// Detailed status endpoint
router.get('/api/status', (req: Request, res: Response) => {
  const dbStatus = mongoose.connection.readyState === 1 ? 'connected' : 'disconnected';
  
  res.json({
    server: 'online',
    environment: process.env.NODE_ENV || 'development',
    database: dbStatus,
    timestamp: new Date().toISOString(),
    version: '1.0.0',
    uptime: process.uptime()
  });
});

export default router;