import { Request, Response } from 'express';
import ProcessStep from '../models/processStep.model';

/**
 * List Process Steps (public)
 * GET /api/process-steps
 */
export async function listProcessSteps(req: Request, res: Response) {
  try {
    const steps = await ProcessStep.find({ published: true })
      .sort({ order: 1 })
      .lean();

    return res.json({ status: 'ok', data: steps });
  } catch (err: any) {
    return res.status(500).json({ status: 'error', message: 'Server error', details: err.message });
  }
}
