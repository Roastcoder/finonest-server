import Joi from 'joi';

export const sendOTPSchema = Joi.object({
  phone: Joi.string()
    .pattern(/^[6-9]\d{9}$/)
    .required()
    .messages({
      'string.pattern.base': 'Phone number must be a valid 10-digit Indian mobile number',
      'any.required': 'Phone number is required'
    })
});

export const verifyOTPSchema = Joi.object({
  phone: Joi.string()
    .pattern(/^[6-9]\d{9}$/)
    .required()
    .messages({
      'string.pattern.base': 'Phone number must be a valid 10-digit Indian mobile number',
      'any.required': 'Phone number is required'
    }),
  otp: Joi.string()
    .pattern(/^\d{6}$/)
    .required()
    .messages({
      'string.pattern.base': 'OTP must be a 6-digit number',
      'any.required': 'OTP is required'
    })
});
