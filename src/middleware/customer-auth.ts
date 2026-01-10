import { Request, Response, NextFunction } from 'express';
import jwt from 'jsonwebtoken';
import Customer from '../models/customer.model';
import { AUTH_DOMAIN } from '../models/types';

/**
 * Middleware to attach customer to request from Authorization header (Bearer token)
 * Only processes tokens with customer domain
 */
export async function attachCustomer(req: Request, _res: Response, next: NextFunction) {
  const auth = req.headers.authorization;
  if (!auth) return next();
  
  const parts = auth.split(' ');
  if (parts.length !== 2 || parts[0] !== 'Bearer') return next();
  
  try {
    const token = parts[1];
    const payload: any = jwt.verify(token, process.env.JWT_SECRET || 'dev-secret');
    
    // Verify this is a customer token
    if (payload && payload.sub && payload.domain === AUTH_DOMAIN.CUSTOMER) {
      const customer = await Customer.findById(payload.sub);
      if (customer && customer.isActive) {
        (req as any).customer = customer;
      }
    }
  } catch (e) {
    // ignore invalid token - customer will be anonymous
  }
  
  return next();
}

/**
 * Middleware to require customer authentication
 */
export function requireCustomerAuth(req: Request, res: Response, next: NextFunction) {
  if (!(req as any).customer) {
    return res.status(401).json({ status: 'error', message: 'Unauthorized - Customer authentication required' });
  }
  return next();
}
