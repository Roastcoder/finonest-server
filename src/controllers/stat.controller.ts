import { Request, Response } from 'express';
import Stat from '../models/stat.model';

/**
 * List Stats (public)
 * GET /api/stats
 */
export async function listStats(req: Request, res: Response) {
  try {
    const stats = await Stat.find({ published: true })
      .sort({ order: 1 })
      .lean();

    return res.json({ status: 'ok', data: stats });
  } catch (err: any) {
    return res.status(500).json({ status: 'error', message: 'Server error', details: err.message });
  }
}
