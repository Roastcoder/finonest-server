import { Request, Response } from 'express';
import FAQ from '../models/faq.model';

/**
 * List FAQs (public)
 * GET /api/faqs
 */
export async function listFAQs(req: Request, res: Response) {
  try {
    const category = req.query.category as string;
    const serviceRef = req.query.serviceRef as string;
    const filter: any = { published: true };
    
    if (category) filter.category = category;
    if (serviceRef) filter.serviceRef = serviceRef;

    const faqs = await FAQ.find(filter)
      .populate('serviceRef', 'slug title')
      .sort({ category: 1, order: 1 })
      .lean();

    return res.json({ status: 'ok', data: faqs });
  } catch (err: any) {
    return res.status(500).json({ status: 'error', message: 'Server error', details: err.message });
  }
}

/**
 * Get FAQ by ID (public)
 * GET /api/faqs/:id
 */
export async function getFAQ(req: Request, res: Response) {
  try {
    const faq = await FAQ.findOne({ _id: req.params.id, published: true })
      .populate('serviceRef', 'slug title')
      .lean();

    if (!faq) {
      return res.status(404).json({ status: 'error', message: 'FAQ not found' });
    }

    return res.json({ status: 'ok', data: faq });
  } catch (err: any) {
    return res.status(500).json({ status: 'error', message: 'Server error', details: err.message });
  }
}
