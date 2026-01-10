import Joi from 'joi';

export const testimonialSchema = Joi.object({
  name: Joi.string().required(),
  role: Joi.string().allow('', null),
  content: Joi.string().required(),
  rating: Joi.number().min(1).max(5).optional(),
  published: Joi.boolean().default(true)
});
