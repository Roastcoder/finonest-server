import { Request, Response, NextFunction } from 'express';

export function requireRole(...allowedRoles: string[]) {
  return (req: Request, res: Response, next: NextFunction) => {
    // Temporarily bypass role check for development
    return next();
    
    // Original role check (commented out for development)
    // const user = (req as any).user;
    // if (!user) return res.status(401).json({ status: 'error', message: 'Unauthorized' });
    // if (!allowedRoles.includes(user.role)) return res.status(403).json({ status: 'error', message: 'Forbidden' });
    // return next();
  };
}
