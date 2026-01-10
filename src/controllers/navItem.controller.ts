import { Request, Response } from 'express';
import NavItem from '../models/navItem.model';

/**
 * List Nav Items (public)
 * GET /api/nav-items
 */
export async function listNavItems(req: Request, res: Response) {
  try {
    const position = req.query.position as string; // 'header', 'footer', or 'both'
    const filter: any = { published: true };
    
    if (position && position !== 'both') {
      filter.$or = [
        { position: position },
        { position: 'both' }
      ];
    }

    const navItems = await NavItem.find(filter)
      .sort({ position: 1, order: 1 })
      .lean();

    // Group by parent/child relationships
    const rootItems = navItems.filter(item => !item.parentNavId);
    const childItems = navItems.filter(item => item.parentNavId);

    const result = rootItems.map(item => ({
      ...item,
      children: childItems.filter(child => 
        child.parentNavId?.toString() === item._id.toString()
      )
    }));

    return res.json({ status: 'ok', data: result });
  } catch (err: any) {
    return res.status(500).json({ status: 'error', message: 'Server error', details: err.message });
  }
}
