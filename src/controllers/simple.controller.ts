import { Request, Response } from 'express';

// Generic simple controllers used for small collections (Banner, Testimonial, Partner, Category, Tag, Media)
export function listModel(Model: any) {
  return async (req: Request, res: Response) => {
    const page = parseInt((req.query.page as string) || '1', 10);
    const limit = Math.min(parseInt((req.query.limit as string) || '20', 10), 100);
    const filter: any = {};
    try {
      const items = await Model.find(filter).skip((page - 1) * limit).limit(limit).sort({ updatedAt: -1 }).lean();
      const total = await Model.countDocuments(filter);
      return res.json({ status: 'ok', data: { items, total, page, limit } });
    } catch (err) {
      return res.status(500).json({ status: 'error', message: 'Server error' });
    }
  };
}

export function getModel(Model: any) {
  return async (req: Request, res: Response) => {
    try {
      const item = await Model.findById(req.params.id).lean();
      if (!item) return res.status(404).json({ status: 'error', message: 'Not found' });
      return res.json({ status: 'ok', data: item });
    } catch (err) {
      return res.status(500).json({ status: 'error', message: 'Server error' });
    }
  };
}

export function createModel(Model: any) {
  return async (req: Request, res: Response) => {
    try {
      const created = await Model.create(req.body);
      return res.status(201).json({ status: 'ok', data: created });
    } catch (err: any) {
      return res.status(400).json({ status: 'error', message: err.message });
    }
  };
}

export function updateModel(Model: any) {
  return async (req: Request, res: Response) => {
    try {
      const updated = await Model.findByIdAndUpdate(req.params.id, req.body, { new: true });
      if (!updated) return res.status(404).json({ status: 'error', message: 'Not found' });
      return res.json({ status: 'ok', data: updated });
    } catch (err: any) {
      return res.status(400).json({ status: 'error', message: err.message });
    }
  };
}

export function deleteModel(Model: any) {
  return async (req: Request, res: Response) => {
    try {
      await Model.findByIdAndDelete(req.params.id);
      return res.status(204).send();
    } catch (err) {
      return res.status(500).json({ status: 'error', message: 'Server error' });
    }
  };
}
