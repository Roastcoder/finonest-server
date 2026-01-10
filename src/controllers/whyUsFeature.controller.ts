import { Request, Response } from 'express';
import WhyUsFeature from '../models/whyUsFeature.model';

/**
 * List WhyUs Features (public)
 * GET /api/why-us-features
 */
export async function listWhyUsFeatures(req: Request, res: Response) {
  try {
    const features = await WhyUsFeature.find({ published: true })
      .populate('image', 'url altText caption')
      .sort({ order: 1 })
      .lean();

    return res.json({ status: 'ok', data: features });
  } catch (err: any) {
    return res.status(500).json({ status: 'error', message: 'Server error', details: err.message });
  }
}
