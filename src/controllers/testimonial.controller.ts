import { Request, Response } from 'express';
import Testimonial from '../models/testimonial.model';

export async function listTestimonials(req: Request, res: Response) {
  try {
    const limit = parseInt((req.query.limit as string) || '50', 10);
    const featured = req.query.featured === 'true';
    
    const filter: any = { published: true };
    if (featured) filter.rating = { $gte: 4 };
    
    const items = await Testimonial.find(filter)
      .populate('avatar', 'url altText')
      .limit(limit)
      .sort({ publishedAt: -1, rating: -1 })
      .lean();
    
    return res.json({ status: 'ok', data: items });
  } catch (err) {
    return res.status(500).json({ status: 'error', message: 'Server error', details: err });
  }
}

export async function getTestimonial(req: Request, res: Response) {
  try {
    const { id } = req.params;
    const item = await Testimonial.findById(id)
      .populate('avatar', 'url altText')
      .lean();
    
    if (!item) return res.status(404).json({ status: 'error', message: 'Not found' });
    return res.json({ status: 'ok', data: item });
  } catch (err) {
    return res.status(500).json({ status: 'error', message: 'Server error', details: err });
  }
}
