import Joi from 'joi';

export const serviceSchema = Joi.object({
  slug: Joi.string().required(),
  title: Joi.string().required(),
  shortDescription: Joi.string().allow('', null),
  longContent: Joi.string().allow('', null),
  images: Joi.array().items(Joi.string()).default([]),
  heroImage: Joi.string().allow('', null),
  rate: Joi.string().allow('', null),
  features: Joi.array().items(Joi.object({ title: Joi.string().required(), description: Joi.string().allow('', null) })).default([]),
  applyFormRef: Joi.string().allow('', null),
  seo: Joi.object({ metaTitle: Joi.string().allow('', null), metaDescription: Joi.string().allow('', null), canonical: Joi.string().allow('', null) }).optional(),
  status: Joi.string().valid('draft', 'published', 'scheduled').default('draft'),
  publishedAt: Joi.date().optional()
});
