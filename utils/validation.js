import Joi from 'joi';

export const schemas = {
  register: Joi.object({
    email: Joi.string().email().required().messages({
      'string.email': 'Please provide a valid email address',
      'any.required': 'Email is required'
    }),
    password: Joi.string().min(6).required().messages({
      'string.min': 'Password must be at least 6 characters',
      'any.required': 'Password is required'
    }),
    fullName: Joi.string().min(2).max(100).required().messages({
      'string.min': 'Full name must be at least 2 characters',
      'string.max': 'Full name cannot exceed 100 characters',
      'any.required': 'Full name is required'
    })
  }),

  login: Joi.object({
    email: Joi.string().email().required(),
    password: Joi.string().required()
  }),

  loanApplication: Joi.object({
    fullName: Joi.string().min(2).max(100).required(),
    email: Joi.string().email().required(),
    phone: Joi.string().pattern(/^[0-9]{10}$/).required().messages({
      'string.pattern.base': 'Phone number must be 10 digits'
    }),
    loanType: Joi.string().valid('home', 'personal', 'business', 'car').required(),
    loanAmount: Joi.number().min(10000).max(10000000).required().messages({
      'number.min': 'Loan amount must be at least ₹10,000',
      'number.max': 'Loan amount cannot exceed ₹1,00,00,000'
    }),
    monthlyIncome: Joi.number().min(0).optional(),
    employmentType: Joi.string().max(100).optional(),
    city: Joi.string().max(100).optional(),
    pincode: Joi.string().pattern(/^[0-9]{6}$/).optional().messages({
      'string.pattern.base': 'Pincode must be 6 digits'
    })
  }),

  contact: Joi.object({
    name: Joi.string().min(2).max(100).required(),
    email: Joi.string().email().required(),
    phone: Joi.string().pattern(/^[0-9]{10}$/).optional(),
    message: Joi.string().min(10).max(1000).required().messages({
      'string.min': 'Message must be at least 10 characters',
      'string.max': 'Message cannot exceed 1000 characters'
    })
  }),

  updateStatus: Joi.object({
    status: Joi.string().valid('pending', 'approved', 'rejected').required()
  }),

  idParam: Joi.object({
    id: Joi.number().integer().positive().required()
  })
};