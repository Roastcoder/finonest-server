import Joi from 'joi';

const BlockProps = Joi.object().unknown(true);

export const pageSchema = Joi.object({
  slug: Joi.string().required(),
  title: Joi.string().allow('', null),
  template: Joi.string().allow(null, ''),
  blocks: Joi.array().items(Joi.object({ type: Joi.string().required(), props: BlockProps }).unknown(true)).default([]),
  status: Joi.string().valid('draft', 'published', 'scheduled').default('draft'),
  publishedAt: Joi.date().optional(),
  seo: Joi.object({ metaTitle: Joi.string().allow('', null), metaDescription: Joi.string().allow('', null), canonical: Joi.string().allow('', null) }).optional()
});
