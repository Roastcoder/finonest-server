import { Request, Response } from 'express';
import Service from '../models/service.model';

export async function getServiceBySlug(req: Request, res: Response) {
  const { slug } = req.params;
  const preview = req.query.preview === 'true';
  try {
    const query: any = { slug };
    if (!preview) query.status = 'published';
    
    const service = await Service.findOne(query)
      .populate('heroImage', 'url altText caption')
      .populate('partnerBanks', 'name logo slug applyLink')
      .populate('relatedServices', 'slug title shortDescription heroImage')
      .populate('seo.openGraphImage', 'url')
      .lean();
    
    if (!service) return res.status(404).json({ status: 'error', message: 'Service not found' });
    return res.json({ status: 'ok', data: service });
  } catch (err) {
    return res.status(500).json({ status: 'error', message: 'Server error', details: err });
  }
}

export async function listServices(req: Request, res: Response) {
  const page = parseInt((req.query.page as string) || '1', 10);
  const limit = Math.min(parseInt((req.query.limit as string) || '20', 10), 100);
  const featured = req.query.featured === 'true';
  const category = req.query.category as string;
  const search = req.query.q as string;
  
  const filter: any = { status: 'published' };
  if (featured) filter.highlight = true;
  if (category) filter.categories = category;
  if (search) {
    filter.$or = [
      { title: { $regex: search, $options: 'i' } },
      { shortDescription: { $regex: search, $options: 'i' } }
    ];
  }
  
  try {
    const items = await Service.find(filter)
      .populate('heroImage', 'url altText')
      .skip((page - 1) * limit)
      .limit(limit)
      .sort({ highlight: -1, updatedAt: -1 })
      .lean();
    const total = await Service.countDocuments(filter);
    return res.json({ status: 'ok', data: { items, total, page, limit } });
  } catch (err) {
    return res.status(500).json({ status: 'error', message: 'Server error', details: err });
  }
}

export async function createService(req: Request, res: Response) {
  try {
    const { sanitizeRichContent } = await import('../utils/sanitize');
    const sanitizedBody = sanitizeRichContent(req.body, ['overview', 'shortDescription']);
    const created = await Service.create(sanitizedBody);
    return res.status(201).json({ status: 'ok', data: created });
  } catch (err: any) {
    return res.status(400).json({ status: 'error', message: err.message });
  }
}

export async function updateService(req: Request, res: Response) {
  try {
    const { id } = req.params;
    const { sanitizeRichContent } = await import('../utils/sanitize');
    const sanitizedBody = sanitizeRichContent(req.body, ['overview', 'shortDescription']);
    const updated = await Service.findByIdAndUpdate(id, sanitizedBody, { new: true });
    if (!updated) return res.status(404).json({ status: 'error', message: 'Not found' });
    return res.json({ status: 'ok', data: updated });
  } catch (err: any) {
    return res.status(400).json({ status: 'error', message: err.message });
  }
}

export async function deleteService(req: Request, res: Response) {
  try {
    const { id } = req.params;
    await Service.findByIdAndDelete(id);
    return res.status(204).send();
  } catch (err) {
    return res.status(500).json({ status: 'error', message: 'Server error' });
  }
}

/**
 * Publish Service
 * POST /api/admin/services/:id/publish
 */
export async function publishService(req: Request, res: Response) {
  try {
    const { id } = req.params;
    const service = await Service.findByIdAndUpdate(
      id,
      { status: 'published', publishedAt: new Date() },
      { new: true }
    );
    if (!service) {
      return res.status(404).json({ status: 'error', message: 'Service not found' });
    }
    return res.json({ status: 'ok', data: service });
  } catch (err: any) {
    return res.status(500).json({ status: 'error', message: 'Server error', details: err.message });
  }
}

/**
 * Schedule Service
 * POST /api/admin/services/:id/schedule
 */
export async function scheduleService(req: Request, res: Response) {
  try {
    const { id } = req.params;
    const { publishedAt } = req.body;
    
    if (!publishedAt) {
      return res.status(400).json({ status: 'error', message: 'publishedAt date is required' });
    }

    const publishDate = new Date(publishedAt);
    if (isNaN(publishDate.getTime())) {
      return res.status(400).json({ status: 'error', message: 'Invalid publishedAt date' });
    }

    const service = await Service.findByIdAndUpdate(
      id,
      { status: 'scheduled', publishedAt: publishDate },
      { new: true }
    );
    if (!service) {
      return res.status(404).json({ status: 'error', message: 'Service not found' });
    }
    return res.json({ status: 'ok', data: service });
  } catch (err: any) {
    return res.status(500).json({ status: 'error', message: 'Server error', details: err.message });
  }
}
