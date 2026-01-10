import { Request, Response } from 'express';
import User from '../models/user.model';
import bcrypt from 'bcrypt';
import { USER_ROLES } from '../models/types';

/**
 * List Users (Admin only)
 * GET /api/admin/users
 */
export async function listUsers(req: Request, res: Response) {
  try {
    const page = parseInt((req.query.page as string) || '1', 10);
    const limit = Math.min(parseInt((req.query.limit as string) || '20', 10), 100);
    const role = req.query.role as string;
    const search = req.query.q as string;

    const filter: any = {};
    if (role) filter.role = role;
    if (search) {
      filter.$or = [
        { email: { $regex: search, $options: 'i' } },
        { name: { $regex: search, $options: 'i' } }
      ];
    }

    const users = await User.find(filter)
      .select('-passwordHash')
      .sort({ createdAt: -1 })
      .skip((page - 1) * limit)
      .limit(limit)
      .lean();

    const total = await User.countDocuments(filter);

    return res.json({
      status: 'ok',
      data: {
        items: users,
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
 * Get User by ID (Admin only)
 * GET /api/admin/users/:id
 */
export async function getUser(req: Request, res: Response) {
  try {
    const user = await User.findById(req.params.id).select('-passwordHash').lean();
    if (!user) {
      return res.status(404).json({ status: 'error', message: 'User not found' });
    }
    return res.json({ status: 'ok', data: user });
  } catch (err: any) {
    return res.status(500).json({ status: 'error', message: 'Server error', details: err.message });
  }
}

/**
 * Create User (SuperAdmin only)
 * POST /api/admin/users
 */
export async function createUser(req: Request, res: Response) {
  try {
    const { email, password, name, role } = req.body;

    // Validation
    if (!email || !password) {
      return res.status(400).json({ status: 'error', message: 'Email and password are required' });
    }

    if (!Object.values(USER_ROLES).includes(role)) {
      return res.status(400).json({ status: 'error', message: 'Invalid role' });
    }

    // Check if user exists
    const existing = await User.findOne({ email: email.toLowerCase() });
    if (existing) {
      return res.status(400).json({ status: 'error', message: 'User with this email already exists' });
    }

    // Hash password
    const passwordHash = await bcrypt.hash(password, 10);

    const user = await User.create({
      email: email.toLowerCase(),
      passwordHash,
      name: name || '',
      role: role || USER_ROLES.CONTRIBUTOR,
      isActive: true,
    });

    const userResponse = user.toObject();
    delete (userResponse as any).passwordHash;

    return res.status(201).json({ status: 'ok', data: userResponse });
  } catch (err: any) {
    return res.status(400).json({ status: 'error', message: err.message });
  }
}

/**
 * Update User (Admin/SuperAdmin)
 * PATCH /api/admin/users/:id
 */
export async function updateUser(req: Request, res: Response) {
  try {
    const { id } = req.params;
    const { email, password, name, role, isActive } = req.body;
    const currentUser = (req as any).user;

    // SuperAdmin can update anyone, Admin can update non-SuperAdmin users
    const targetUser = await User.findById(id);
    if (!targetUser) {
      return res.status(404).json({ status: 'error', message: 'User not found' });
    }

    // Role-based restrictions
    if (currentUser.role !== USER_ROLES.SUPERADMIN) {
      if (targetUser.role === USER_ROLES.SUPERADMIN) {
        return res.status(403).json({ status: 'error', message: 'Cannot modify SuperAdmin user' });
      }
      if (role && role === USER_ROLES.SUPERADMIN) {
        return res.status(403).json({ status: 'error', message: 'Cannot assign SuperAdmin role' });
      }
    }

    const updates: any = {};
    if (email && email !== targetUser.email) {
      const existing = await User.findOne({ email: email.toLowerCase() });
      if (existing && existing._id.toString() !== id) {
        return res.status(400).json({ status: 'error', message: 'Email already in use' });
      }
      updates.email = email.toLowerCase();
    }
    if (name !== undefined) updates.name = name;
    if (role && Object.values(USER_ROLES).includes(role)) {
      updates.role = role;
    }
    if (isActive !== undefined) updates.isActive = isActive;
    if (password) {
      updates.passwordHash = await bcrypt.hash(password, 10);
    }

    const updated = await User.findByIdAndUpdate(id, { $set: updates }, { new: true })
      .select('-passwordHash')
      .lean();

    return res.json({ status: 'ok', data: updated });
  } catch (err: any) {
    return res.status(400).json({ status: 'error', message: err.message });
  }
}

/**
 * Delete User (SuperAdmin only)
 * DELETE /api/admin/users/:id
 */
export async function deleteUser(req: Request, res: Response) {
  try {
    const { id } = req.params;
    const currentUser = (req as any).user;

    // Prevent self-deletion
    if (currentUser._id.toString() === id) {
      return res.status(400).json({ status: 'error', message: 'Cannot delete your own account' });
    }

    const user = await User.findById(id);
    if (!user) {
      return res.status(404).json({ status: 'error', message: 'User not found' });
    }

    // Prevent deletion of SuperAdmin
    if (user.role === USER_ROLES.SUPERADMIN) {
      return res.status(403).json({ status: 'error', message: 'Cannot delete SuperAdmin user' });
    }

    await User.findByIdAndDelete(id);
    return res.status(204).send();
  } catch (err: any) {
    return res.status(500).json({ status: 'error', message: 'Server error', details: err.message });
  }
}
