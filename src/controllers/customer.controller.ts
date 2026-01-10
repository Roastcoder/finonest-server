import { Request, Response } from 'express';
import Customer from '../models/customer.model';
import Lead from '../models/lead.model';
import { sanitizeRichContent } from '../utils/sanitize';

/**
 * Get customer profile
 * GET /api/customer/profile
 */
export async function getCustomerProfile(req: Request, res: Response) {
  try {
    const customer = (req as any).customer;
    if (!customer) {
      return res.status(401).json({ status: 'error', message: 'Unauthorized' });
    }

    const profile = await Customer.findById(customer._id)
      .select('-__v')
      .lean();

    return res.json({ status: 'ok', data: profile });
  } catch (err: any) {
    return res.status(500).json({ status: 'error', message: 'Server error', details: err.message });
  }
}

/**
 * Update customer profile
 * PATCH /api/customer/profile
 */
export async function updateCustomerProfile(req: Request, res: Response) {
  try {
    const customer = (req as any).customer;
    if (!customer) {
      return res.status(401).json({ status: 'error', message: 'Unauthorized' });
    }

    const allowedFields = ['name', 'email', 'profile', 'preferences'];
    const updates: any = {};

    for (const field of allowedFields) {
      if (req.body[field] !== undefined) {
        updates[field] = req.body[field];
      }
    }

    // Sanitize profile data
    if (updates.profile) {
      updates.profile = sanitizeRichContent(updates.profile, ['address', 'city', 'state']);
    }

    const updated = await Customer.findByIdAndUpdate(
      customer._id,
      { $set: updates },
      { new: true, runValidators: true }
    ).select('-__v');

    if (!updated) {
      return res.status(404).json({ status: 'error', message: 'Customer not found' });
    }

    return res.json({ status: 'ok', data: updated });
  } catch (err: any) {
    return res.status(400).json({ status: 'error', message: err.message });
  }
}

/**
 * Get customer's loan applications
 * GET /api/customer/applications
 */
export async function getCustomerApplications(req: Request, res: Response) {
  try {
    const customer = (req as any).customer;
    if (!customer) {
      return res.status(401).json({ status: 'error', message: 'Unauthorized' });
    }

    const page = parseInt((req.query.page as string) || '1', 10);
    const limit = Math.min(parseInt((req.query.limit as string) || '20', 10), 100);
    const status = req.query.status as string;

    const filter: any = { customer: customer._id };
    if (status) filter.status = status;

    const applications = await Lead.find(filter)
      .populate('formRef', 'name slug')
      .populate('files', 'url filename')
      .sort({ createdAt: -1 })
      .skip((page - 1) * limit)
      .limit(limit)
      .lean();

    const total = await Lead.countDocuments(filter);

    return res.json({
      status: 'ok',
      data: {
        items: applications,
        total,
        page,
        limit,
      },
    });
  } catch (err: any) {
    return res.status(500).json({ status: 'error', message: 'Server error', details: err.message });
  }
}

/**
 * Get single application details
 * GET /api/customer/applications/:id
 */
export async function getApplicationDetails(req: Request, res: Response) {
  try {
    const customer = (req as any).customer;
    const { id } = req.params;

    if (!customer) {
      return res.status(401).json({ status: 'error', message: 'Unauthorized' });
    }

    const application = await Lead.findOne({
      _id: id,
      customer: customer._id,
    })
      .populate('formRef', 'name slug fields')
      .populate('files', 'url filename mimeType size')
      .populate('assignedTo', 'name email')
      .lean();

    if (!application) {
      return res.status(404).json({ status: 'error', message: 'Application not found' });
    }

    return res.json({ status: 'ok', data: application });
  } catch (err: any) {
    return res.status(500).json({ status: 'error', message: 'Server error', details: err.message });
  }
}

/**
 * Get customer dashboard stats
 * GET /api/customer/dashboard
 */
export async function getCustomerDashboard(req: Request, res: Response) {
  try {
    const customer = (req as any).customer;
    if (!customer) {
      return res.status(401).json({ status: 'error', message: 'Unauthorized' });
    }

    const [totalApplications, pendingApplications, approvedApplications, recentApplications] = await Promise.all([
      Lead.countDocuments({ customer: customer._id }),
      Lead.countDocuments({ customer: customer._id, status: { $in: ['new', 'contacted', 'qualified'] } }),
      Lead.countDocuments({ customer: customer._id, status: 'approved' }),
      Lead.find({ customer: customer._id })
        .sort({ createdAt: -1 })
        .limit(5)
        .select('loanType loanAmount status createdAt applicationNumber')
        .lean(),
    ]);

    return res.json({
      status: 'ok',
      data: {
        stats: {
          totalApplications,
          pendingApplications,
          approvedApplications,
          rejectedApplications: await Lead.countDocuments({ customer: customer._id, status: 'rejected' }),
        },
        recentApplications,
        profile: {
          name: customer.name,
          phone: customer.phone,
          email: customer.email,
          isVerified: customer.isVerified,
        },
      },
    });
  } catch (err: any) {
    return res.status(500).json({ status: 'error', message: 'Server error', details: err.message });
  }
}
