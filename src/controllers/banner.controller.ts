import { Request, Response } from 'express';
import Banner from '../models/banner.model';

export async function listBanners(req: Request, res: Response) {
  try {
    const now = new Date();
    
    const items = await Banner.find({
      active: true,
      $and: [
        {
          $or: [
            { startDate: { $exists: false } },
            { startDate: { $lte: now } }
          ]
        },
        {
          $or: [
            { endDate: { $exists: false } },
            { endDate: { $gte: now } }
          ]
        }
      ]
    })
      .populate('image', 'url altText')
      .sort({ priority: -1, createdAt: -1 })
      .lean();
    
    return res.json({ status: 'ok', data: items });
  } catch (err) {
    return res.status(500).json({ status: 'error', message: 'Server error', details: err });
  }
}
