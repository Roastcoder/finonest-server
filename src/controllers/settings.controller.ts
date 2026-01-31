import { Request, Response } from 'express';
import SiteSettings from '../models/settings.model';

export async function getSiteSettings(req: Request, res: Response) {
  try {
    // Singleton pattern: get or create single settings document
    let settings = await SiteSettings.findOne({}).populate('logo favicon', 'url').lean();
    
    if (!settings) {
      // Create default settings if none exist
      settings = await SiteSettings.create({
        siteTitle: 'Finonest',
        siteDescription: 'Smart loans, simple process',
        maintenanceMode: false
      });
    }
    
    return res.json({ status: 'ok', data: settings });
  } catch (err) {
    return res.status(500).json({ status: 'error', message: 'Server error', details: err });
  }
}
