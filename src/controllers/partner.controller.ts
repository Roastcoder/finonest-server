import { Request, Response } from 'express';
import Partner from '../models/partner.model';

export async function listPartners(req: Request, res: Response) {
  try {
    const featured = req.query.featured === 'true';
    const filter: any = { status: 'published' };
    if (featured) filter.featured = true;
    
    const items = await Partner.find(filter)
      .populate('logo', 'url altText')
      .sort({ featured: -1, name: 1 })
      .lean();
    
    return res.json({ status: 'ok', data: items });
  } catch (err) {
    return res.status(500).json({ status: 'error', message: 'Server error', details: err });
  }
}

export async function getPartnerBySlug(req: Request, res: Response) {
  try {
    const { slug } = req.params;
    const item = await Partner.findOne({ slug, status: 'published' })
      .populate('logo', 'url altText')
      .lean();
    
    if (!item) return res.status(404).json({ status: 'error', message: 'Not found' });
    return res.json({ status: 'ok', data: item });
  } catch (err) {
    return res.status(500).json({ status: 'error', message: 'Server error', details: err });
  }
}
