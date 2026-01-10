import Joi from 'joi';

export const customerProfileSchema = Joi.object({
  name: Joi.string().min(2).max(100).optional(),
  email: Joi.string().email().optional(),
  profile: Joi.object({
    dateOfBirth: Joi.date().optional(),
    address: Joi.string().max(500).optional(),
    city: Joi.string().max(100).optional(),
    state: Joi.string().max(100).optional(),
    pincode: Joi.string().pattern(/^\d{6}$/).optional(),
    panNumber: Joi.string().pattern(/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/).optional(),
    aadhaarNumber: Joi.string().pattern(/^\d{12}$/).optional(),
  }).optional(),
  preferences: Joi.object({
    notifications: Joi.boolean().optional(),
    marketing: Joi.boolean().optional(),
  }).optional(),
}).min(1); // At least one field required
