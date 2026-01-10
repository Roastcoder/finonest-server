import { Request, Response } from 'express';
import Lead from '../models/lead.model';
import { Parser } from 'json2csv';

export async function exportLeadsCSV(req: Request, res: Response) {
  try {
    const leads = await Lead.find({}).lean();
    const fields = Object.keys(leads.length ? leads[0] : { id: 'id' });
    const parser = new Parser({ fields });
    const csv = parser.parse(leads.map(l => ({ ...l, id: l._id })));
    res.header('Content-Type', 'text/csv');
    res.attachment('leads.csv');
    return res.send(csv);
  } catch (err) {
    return res.status(500).json({ status: 'error', message: 'Server error' });
  }
}
