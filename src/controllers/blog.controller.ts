import { Request, Response } from 'express';
import BlogPost from '../models/blog.model';

export async function getPostBySlug(req: Request, res: Response) {
  const { slug } = req.params;
  const preview = req.query.preview === 'true';
  try {
    const query: any = { slug };
    if (!preview) query.status = 'published';
    
    const post = await BlogPost.findOne(query)
      .populate('author', 'name email')
      .populate('categories', 'name slug')
      .populate('featuredImage', 'url altText caption')
      .populate('seo.openGraphImage', 'url')
      .lean();
    
    if (!post) return res.status(404).json({ status: 'error', message: 'Not found' });
    
    // Get related posts by tags or categories
    if (post.tags && post.tags.length > 0) {
      const related = await BlogPost.find({
        _id: { $ne: post._id },
        status: 'published',
        tags: { $in: post.tags }
      })
        .limit(3)
        .select('slug title excerpt featuredImage publishedAt')
        .populate('featuredImage', 'url')
        .lean();
      (post as any).relatedPosts = related;
    }
    
    return res.json({ status: 'ok', data: post });
  } catch (err) {
    return res.status(500).json({ status: 'error', message: 'Server error', details: err });
  }
}

export async function listPosts(req: Request, res: Response) {
  const page = parseInt((req.query.page as string) || '1', 10);
  const limit = Math.min(parseInt((req.query.limit as string) || '10', 10), 100);
  const filter: any = { status: 'published' };
  
  if (req.query.category) filter.categories = req.query.category;
  if (req.query.tag) filter.tags = req.query.tag;
  if (req.query.q) {
    filter.$or = [
      { title: { $regex: req.query.q, $options: 'i' } },
      { excerpt: { $regex: req.query.q, $options: 'i' } },
      { tags: { $in: [req.query.q] } }
    ];
  }
  
  try {
    const items = await BlogPost.find(filter)
      .populate('categories', 'name slug')
      .populate('featuredImage', 'url altText')
      .skip((page - 1) * limit)
      .limit(limit)
      .sort({ publishedAt: -1 })
      .lean();
    const total = await BlogPost.countDocuments(filter);
    return res.json({ status: 'ok', data: { items, total, page, limit } });
  } catch (err) {
    return res.status(500).json({ status: 'error', message: 'Server error', details: err });
  }
}

export async function createPost(req: Request, res: Response) {
  try {
    const { sanitizeRichContent } = await import('../utils/sanitize');
    const sanitizedBody = sanitizeRichContent(req.body, ['content', 'excerpt']);
    const created = await BlogPost.create(sanitizedBody);
    return res.status(201).json({ status: 'ok', data: created });
  } catch (err: any) {
    return res.status(400).json({ status: 'error', message: err.message });
  }
}

export async function updatePost(req: Request, res: Response) {
  try {
    const { id } = req.params;
    const { sanitizeRichContent } = await import('../utils/sanitize');
    const sanitizedBody = sanitizeRichContent(req.body, ['content', 'excerpt']);
    const updated = await BlogPost.findByIdAndUpdate(id, sanitizedBody, { new: true });
    if (!updated) return res.status(404).json({ status: 'error', message: 'Not found' });
    return res.json({ status: 'ok', data: updated });
  } catch (err: any) {
    return res.status(400).json({ status: 'error', message: err.message });
  }
}

export async function deletePost(req: Request, res: Response) {
  try {
    const { id } = req.params;
    await BlogPost.findByIdAndDelete(id);
    return res.status(204).send();
  } catch (err) {
    return res.status(500).json({ status: 'error', message: 'Server error' });
  }
}

export async function publishPost(req: Request, res: Response) {
  try {
    const { id } = req.params;
    const updated = await BlogPost.findByIdAndUpdate(
      id,
      { status: 'published', publishedAt: new Date() },
      { new: true }
    );
    if (!updated) return res.status(404).json({ status: 'error', message: 'Not found' });
    return res.json({ status: 'ok', data: updated });
  } catch (err) {
    return res.status(500).json({ status: 'error', message: 'Server error' });
  }
}

export async function schedulePost(req: Request, res: Response) {
  try {
    const { id } = req.params;
    const { publishedAt } = req.body;
    if (!publishedAt) {
      return res.status(400).json({ status: 'error', message: 'publishedAt is required' });
    }
    const updated = await BlogPost.findByIdAndUpdate(
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
