import Joi from 'joi';

export const bannerSchema = Joi.object({
  title: Joi.string().allow('', null),
  link: Joi.string().allow('', null),
  priority: Joi.number().integer().min(0).default(0),
  startDate: Joi.date().optional(),
  endDate: Joi.date().optional(),
  active: Joi.boolean().default(true)
});
