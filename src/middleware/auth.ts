import { Request, Response, NextFunction } from 'express';
import jwt from 'jsonwebtoken';
import User from '../models/user.model';
import { AUTH_DOMAIN } from '../models/types';

/**
 * Middleware to attach admin user to request from Authorization header (Bearer token)
 * Only processes tokens with admin domain
 */
export async function attachUser(req: Request, _res: Response, next: NextFunction) {
  const auth = req.headers.authorization;
  if (!auth) return next();
  
  const parts = auth.split(' ');
  if (parts.length !== 2 || parts[0] !== 'Bearer') return next();
  
  try {
    const token = parts[1];
    const payload: any = jwt.verify(token, process.env.JWT_SECRET || 'dev-secret');
    
    // Verify this is an admin token (default or explicit admin domain)
    if (payload && payload.sub && (!payload.domain || payload.domain === AUTH_DOMAIN.ADMIN)) {
      const user = await User.findById(payload.sub).select('-passwordHash');
      if (user && user.isActive) {
        (req as any).user = user;
      }
    }
  } catch (e) {
    // ignore invalid token - user will be anonymous
  }
  
  return next();
}

/**
 * Middleware to require admin authentication
 */
export function requireAuth(req: Request, res: Response, next: NextFunction) {
  if (!(req as any).user) {
    return res.status(401).json({ status: 'error', message: 'Unauthorized - Admin authentication required' });
  }
  return next();
}
