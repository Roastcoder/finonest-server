import Joi from 'joi';

export const blogSchema = Joi.object({
  slug: Joi.string().required(),
  title: Joi.string().required(),
  excerpt: Joi.string().allow('', null),
  content: Joi.string().required(),
  featuredImage: Joi.string().allow('', null),
  author: Joi.string().allow('', null),
  categories: Joi.array().items(Joi.string()).default([]),
  tags: Joi.array().items(Joi.string()).default([]),
  status: Joi.string().valid('draft', 'published', 'scheduled').default('draft'),
  publishedAt: Joi.date().optional(),
  seo: Joi.object({ metaTitle: Joi.string().allow('', null), metaDescription: Joi.string().allow('', null), canonical: Joi.string().allow('', null) }).optional()
});
