import { Request, Response } from 'express';
import Footer from '../models/footer.model';

/**
 * Get Footer (public - singleton)
 * GET /api/footer
 */
export async function getFooter(req: Request, res: Response) {
  try {
    const footer = await Footer.findOne({ published: true })
      .sort({ publishedAt: -1 })
      .lean();

    if (!footer) {
      // Return empty structure if no footer configured
      return res.json({
        status: 'ok',
        data: {
          quickLinks: [],
          contactInfo: {},
          socialLinks: {},
          legalLinks: [],
          copyrightText: '© 2025 Finonest. All rights reserved.'
        }
      });
    }

    return res.json({ status: 'ok', data: footer });
  } catch (err: any) {
    return res.status(500).json({ status: 'error', message: 'Server error', details: err.message });
  }
}
