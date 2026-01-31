import { Request, Response } from 'express';
import Page from '../models/page.model';

export async function getPageBySlug(req: Request, res: Response) {
  const { slug } = req.params;
  const preview = req.query.preview === 'true';
  try {
    const query: any = { slug };
    if (!preview) query.status = 'published';
    
    const page = await Page.findOne(query)
      .populate({ path: 'seo.openGraphImage', select: 'url altText caption' })
      .lean();
    
    if (!page) return res.status(404).json({ status: 'error', message: 'Page not found' });
    
    // Populate media references in blocks if needed
    if (page.blocks) {
      // Blocks may contain media references that need population
      // This is handled at render time in frontend
    }
    
    return res.json({ status: 'ok', data: page });
  } catch (err) {
    return res.status(500).json({ status: 'error', message: 'Server error', details: err });
  }
}

export async function listPages(req: Request, res: Response) {
  const page = parseInt((req.query.page as string) || '1', 10);
  const limit = Math.min(parseInt((req.query.limit as string) || '20', 10), 100);
  try {
    const items = await Page.find({}).skip((page - 1) * limit).limit(limit).sort({ updatedAt: -1 }).lean();
    const total = await Page.countDocuments({});
    return res.json({ status: 'ok', data: { items, total, page, limit } });
  } catch (err) {
    return res.status(500).json({ status: 'error', message: 'Server error', details: err });
  }
}

export async function createPage(req: Request, res: Response) {
  try {
    const { sanitizePageBlocks, sanitizeRichContent } = await import('../utils/sanitize');
    let payload = req.body;
    
    // Sanitize blocks if present
    if (payload.blocks && Array.isArray(payload.blocks)) {
      payload.blocks = sanitizePageBlocks(payload.blocks);
    }
    
    // Sanitize other rich content fields
    payload = sanitizeRichContent(payload, ['title', 'description']);
    
    const created = await Page.create(payload);
    return res.status(201).json({ status: 'ok', data: created });
  } catch (err: any) {
    return res.status(400).json({ status: 'error', message: err.message, details: err });
  }
}

export async function updatePage(req: Request, res: Response) {
  try {
    const { id } = req.params;
    const { sanitizePageBlocks, sanitizeRichContent } = await import('../utils/sanitize');
    let payload = req.body;
    
    // Sanitize blocks if present
    if (payload.blocks && Array.isArray(payload.blocks)) {
      payload.blocks = sanitizePageBlocks(payload.blocks);
    }
    
    // Sanitize other rich content fields
    payload = sanitizeRichContent(payload, ['title', 'description']);
    
    const updated = await Page.findByIdAndUpdate(id, payload, { new: true });
    if (!updated) return res.status(404).json({ status: 'error', message: 'Not found' });
    return res.json({ status: 'ok', data: updated });
  } catch (err: any) {
    return res.status(400).json({ status: 'error', message: err.message });
  }
}

export async function deletePage(req: Request, res: Response) {
  try {
    const { id } = req.params;
    await Page.findByIdAndDelete(id);
    return res.status(204).send();
  } catch (err) {
    return res.status(500).json({ status: 'error', message: 'Server error' });
  }
}

export async function publishPage(req: Request, res: Response) {
  try {
    const { id } = req.params;
    const updated = await Page.findByIdAndUpdate(
      id,
      { status: 'published', publishedAt: new Date() },
      { new: true }
    );
    if (!updated) return res.status(404).json({ status: 'error', message: 'Not found' });
    return res.json({ status: 'ok', data: updated });
  } catch (err: any) {
    return res.status(400).json({ status: 'error', message: err.message });
  }
}

export async function schedulePage(req: Request, res: Response) {
  try {
    const { id } = req.params;
    const { publishedAt } = req.body;
    if (!publishedAt) {
      return res.status(400).json({ status: 'error', message: 'publishedAt is required' });
    }
    const updated = await Page.findByIdAndUpdate(
      id,
      { status: 'scheduled', publishedAt: new Date(publishedAt) },
      { new: true }
    );
    if (!updated) return res.status(404).json({ status: 'error', message: 'Not found' });
    return res.json({ status: 'ok', data: updated });
  } catch (err: any) {
    return res.status(400).json({ status: 'error', message: err.message });
  }
}
