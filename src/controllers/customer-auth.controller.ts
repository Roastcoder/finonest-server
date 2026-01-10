import { Request, Response } from 'express';
import crypto from 'crypto';
import Customer from '../models/customer.model';
import OTP from '../models/otp.model';
import RefreshToken from '../models/refreshToken.model';
import { signAccessToken } from '../utils/jwt';
import { AUTH_DOMAIN } from '../models/types';

const REFRESH_TTL_DAYS = parseInt(process.env.REFRESH_TTL_DAYS || '30', 10);
const OTP_EXPIRY_MINUTES = parseInt(process.env.OTP_EXPIRY_MINUTES || '10', 10);
const MAX_OTP_ATTEMPTS = parseInt(process.env.MAX_OTP_ATTEMPTS || '5', 10);

function hashToken(token: string) {
  return crypto.createHash('sha256').update(token).digest('hex');
}

function hashOTP(code: string) {
  return crypto.createHash('sha256').update(code).digest('hex');
}

function generateOTP(): string {
  // Generate 6-digit OTP
  return Math.floor(100000 + Math.random() * 900000).toString();
}

async function sendOTP(phone: string, code: string): Promise<void> {
  const smsService = (await import('../services/sms.service')).default;
  await smsService.sendOTP(phone, code, OTP_EXPIRY_MINUTES);
}

function createRefreshToken(userId: string, ip: string, domain: string) {
  const token = crypto.randomBytes(64).toString('hex');
  const expiresAt = new Date(Date.now() + REFRESH_TTL_DAYS * 24 * 60 * 60 * 1000);
  const tokenHash = hashToken(token);
  return RefreshToken.create({ 
    user: userId, 
    tokenHash, 
    expiresAt, 
    createdByIp: ip,
    domain // Store auth domain
  });
}

/**
 * Send OTP to customer's phone number
 * POST /api/customer/auth/send-otp
 */
export async function sendOTPToCustomer(req: Request, res: Response) {
  const { phone } = req.body;
  
  if (!phone) {
    return res.status(400).json({ status: 'error', message: 'Phone number is required' });
  }

  // Validate phone number format (Indian: 10 digits)
  const phoneRegex = /^[6-9]\d{9}$/;
  if (!phoneRegex.test(phone.replace(/\D/g, ''))) {
    return res.status(400).json({ status: 'error', message: 'Invalid phone number format' });
  }

  const normalizedPhone = phone.replace(/\D/g, '');

  try {
    // Check for recent OTP to prevent spam
    const recentOTP = await OTP.findOne({
      phone: normalizedPhone,
      purpose: 'login',
      verified: false,
      expiresAt: { $gt: new Date() }
    }).sort({ createdAt: -1 });

    if (recentOTP) {
      const secondsSinceLastOTP = Math.floor((Date.now() - recentOTP.createdAt.getTime()) / 1000);
      if (secondsSinceLastOTP < 60) {
        return res.status(429).json({ 
          status: 'error', 
          message: 'Please wait before requesting another OTP',
          retryAfter: 60 - secondsSinceLastOTP
        });
      }
    }

    // Generate and store OTP
    const otpCode = generateOTP();
    const otpHash = hashOTP(otpCode);
    const expiresAt = new Date(Date.now() + OTP_EXPIRY_MINUTES * 60 * 1000);

    // Invalidate previous unverified OTPs for this phone
    await OTP.updateMany(
      { phone: normalizedPhone, purpose: 'login', verified: false },
      { verified: true } // Mark as used
    );

    await OTP.create({
      phone: normalizedPhone,
      code: otpHash,
      purpose: 'login',
      expiresAt,
      ip: req.ip,
    });

    // Send OTP via SMS
    await sendOTP(normalizedPhone, otpCode);

    // Create or update customer record
    await Customer.findOneAndUpdate(
      { phone: normalizedPhone },
      { phone: normalizedPhone, isActive: true },
      { upsert: true, new: true }
    );

    return res.json({ 
      status: 'ok', 
      message: 'OTP sent successfully',
      expiresIn: OTP_EXPIRY_MINUTES * 60
    });
  } catch (err: any) {
    console.error('Send OTP error:', err);
    return res.status(500).json({ status: 'error', message: 'Failed to send OTP' });
  }
}

/**
 * Verify OTP and login customer
 * POST /api/customer/auth/verify-otp
 */
export async function verifyOTPAndLogin(req: Request, res: Response) {
  const { phone, otp } = req.body;

  if (!phone || !otp) {
    return res.status(400).json({ status: 'error', message: 'Phone and OTP are required' });
  }

  const normalizedPhone = phone.replace(/\D/g, '');
  const otpHash = hashOTP(otp);

  try {
    // Find valid OTP
    const otpRecord = await OTP.findOne({
      phone: normalizedPhone,
      code: otpHash,
      purpose: 'login',
      verified: false,
      expiresAt: { $gt: new Date() }
    }).sort({ createdAt: -1 });

    if (!otpRecord) {
      // Increment attempts for failed verification
      await OTP.updateOne(
        { phone: normalizedPhone, purpose: 'login', verified: false, expiresAt: { $gt: new Date() } },
        { $inc: { attempts: 1 } }
      );

      return res.status(401).json({ status: 'error', message: 'Invalid or expired OTP' });
    }

    // Check max attempts
    if (otpRecord.attempts >= MAX_OTP_ATTEMPTS) {
      return res.status(429).json({ status: 'error', message: 'Too many failed attempts. Please request a new OTP' });
    }

    // Mark OTP as verified
    otpRecord.verified = true;
    otpRecord.verifiedAt = new Date();
    await otpRecord.save();

    // Get or create customer
    let customer = await Customer.findOne({ phone: normalizedPhone });
    if (!customer) {
      customer = await Customer.create({
        phone: normalizedPhone,
        isVerified: true,
        isActive: true,
        lastLoginAt: new Date(),
      });
    } else {
      customer.isVerified = true;
      customer.lastLoginAt = new Date();
      await customer.save();
    }

    // Generate tokens
    const accessToken = signAccessToken({ 
      sub: customer._id, 
      role: 'customer',
      domain: AUTH_DOMAIN.CUSTOMER 
    });

    const refreshTokenRaw = crypto.randomBytes(64).toString('hex');
    const refresh = await createRefreshToken(customer._id.toString(), req.ip, AUTH_DOMAIN.CUSTOMER);
    refresh.tokenHash = hashToken(refreshTokenRaw);
    await refresh.save();

    // Set httpOnly cookie for refresh token
    res.cookie('customerRefreshToken', refreshTokenRaw, {
      httpOnly: true,
      secure: process.env.NODE_ENV === 'production',
      sameSite: 'lax',
      path: '/api/customer/auth/refresh',
      maxAge: REFRESH_TTL_DAYS * 24 * 60 * 60 * 1000
    });

    return res.json({
      status: 'ok',
      data: {
        customer: {
          id: customer._id,
          phone: customer.phone,
          name: customer.name,
          email: customer.email,
          isVerified: customer.isVerified,
        },
        accessToken
      }
    });
  } catch (err: any) {
    console.error('Verify OTP error:', err);
    return res.status(500).json({ status: 'error', message: 'Failed to verify OTP' });
  }
}

/**
 * Refresh customer access token
 * POST /api/customer/auth/refresh
 */
export async function refreshCustomerToken(req: Request, res: Response) {
  const token = req.cookies?.customerRefreshToken || req.headers['x-refresh-token'];
  
  if (!token) {
    return res.status(401).json({ status: 'error', message: 'No refresh token' });
  }

  const tokenHash = hashToken(token);
  const stored = await RefreshToken.findOne({ tokenHash, domain: AUTH_DOMAIN.CUSTOMER });

  if (!stored || !stored.isActive()) {
    return res.status(401).json({ status: 'error', message: 'Invalid refresh token' });
  }

  // Get customer based on domain
  const customer = await Customer.findById(stored.user);
  if (!customer || !customer.isActive) {
    return res.status(401).json({ status: 'error', message: 'Customer not found or inactive' });
  }

  // Rotate refresh token
  stored.revokedAt = new Date();
  stored.revokedByIp = req.ip;
  const newRefreshRaw = crypto.randomBytes(64).toString('hex');
  const newHash = hashToken(newRefreshRaw);
  const newTokenDoc = await RefreshToken.create({
    user: customer._id,
    tokenHash: newHash,
    expiresAt: new Date(Date.now() + REFRESH_TTL_DAYS * 24 * 60 * 60 * 1000),
    createdByIp: req.ip,
    domain: AUTH_DOMAIN.CUSTOMER
  });
  stored.replacedByToken = newTokenDoc._id.toString();
  await stored.save();

  const accessToken = signAccessToken({
    sub: customer._id,
    role: 'customer',
    domain: AUTH_DOMAIN.CUSTOMER
  });

  res.cookie('customerRefreshToken', newRefreshRaw, {
    httpOnly: true,
    secure: process.env.NODE_ENV === 'production',
    sameSite: 'lax',
    path: '/api/customer/auth/refresh',
    maxAge: REFRESH_TTL_DAYS * 24 * 60 * 60 * 1000
  });

  return res.json({ status: 'ok', data: { accessToken } });
}

/**
 * Logout customer
 * POST /api/customer/auth/logout
 */
export async function logoutCustomer(req: Request, res: Response) {
  const token = req.cookies?.customerRefreshToken || req.headers['x-refresh-token'];
  
  if (token) {
    const tokenHash = hashToken(token);
    const stored = await RefreshToken.findOne({ tokenHash, domain: AUTH_DOMAIN.CUSTOMER });
    if (stored) {
      stored.revokedAt = new Date();
      stored.revokedByIp = req.ip;
      await stored.save();
    }
  }

  res.clearCookie('customerRefreshToken', { path: '/api/customer/auth/refresh' });
  return res.json({ status: 'ok' });
}

/**
 * Get current customer profile
 * GET /api/customer/auth/me
 */
export async function getCustomerProfile(req: Request, res: Response) {
  const customer = (req as any).customer;
  if (!customer) {
    return res.status(401).json({ status: 'error', message: 'Unauthorized' });
  }

  return res.json({ status: 'ok', data: customer });
}
