import mongoose from 'mongoose';
import app from './app';
import { autoSeedData } from './utils/autoSeed';

const PORT = process.env.PORT || 4000;
const MONGO_URI = process.env.MONGO_URI || 'mongodb://localhost:27017/finonest-dev';

// Graceful shutdown handler
process.on('SIGTERM', () => {
  console.log('SIGTERM received, shutting down gracefully');
  mongoose.connection.close(() => {
    process.exit(0);
  });
});

// MongoDB connection disabled - using PHP/MySQL APIs instead
console.log('⚠️  Node.js API server disabled - using PHP/MySQL APIs');
console.log('✅ DSA APIs available at PHP endpoints');

// Keep process alive but don't start server
setInterval(() => {
  // Keep alive
}, 30000);
