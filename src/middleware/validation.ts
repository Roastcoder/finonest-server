import { Request, Response, NextFunction } from 'express';
import Joi from 'joi';
// lightweight sanitizer for strings: remove script tags and inline event handlers
function simpleSanitize(input: string) {
  return input
    .replace(/<script[\s\S]*?>[\s\S]*?<\/script>/gi, '')
    .replace(/on\w+\s*=\s*"[^"]*"/gi, '')
    .replace(/on\w+\s*=\s*'[^']*'/gi, '')
    .replace(/javascript:/gi, '');
}

function sanitizeStringsDeep(obj: unknown): unknown {
  if (obj == null) return obj;
  if (typeof obj === 'string') return simpleSanitize(obj);
  if (Array.isArray(obj)) return obj.map((v) => sanitizeStringsDeep(v));
  if (typeof obj === 'object') {
    const out: Record<string, unknown> = {};
    for (const k of Object.keys(obj as Record<string, unknown>)) {
      out[k] = sanitizeStringsDeep((obj as Record<string, unknown>)[k]);
    }
    return out;
  }
  return obj;
}

export function validateBody(schema: Joi.ObjectSchema, options: Joi.ValidationOptions = {}) {
  return (req: Request, res: Response, next: NextFunction) => {
    const { error, value } = schema.validate(req.body, { abortEarly: false, stripUnknown: true, ...options });
    if (error) return res.status(400).json({ status: 'error', message: 'Validation failed', details: error.details });
    // sanitize all strings in payload to remove dangerous HTML
    req.body = sanitizeStringsDeep(value);
    return next();
  };
}

export function validateQuery(schema: Joi.ObjectSchema, options: Joi.ValidationOptions = {}) {
  return (req: Request, res: Response, next: NextFunction) => {
    const { error, value } = schema.validate(req.query, { abortEarly: false, stripUnknown: true, ...options });
    if (error) return res.status(400).json({ status: 'error', message: 'Validation failed', details: error.details });
    (req as unknown as { query: unknown }).query = value;
    return next();
  };
}
