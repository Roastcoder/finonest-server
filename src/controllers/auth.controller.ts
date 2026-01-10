import { Request, Response } from 'express';
import crypto from 'crypto';
import bcrypt from 'bcrypt';
import User from '../models/user.model';
import RefreshToken from '../models/refreshToken.model';
import { signAccessToken } from '../utils/jwt';

const REFRESH_TTL_DAYS = parseInt(process.env.REFRESH_TTL_DAYS || '30', 10);

function hashToken(token: string) {
  return crypto.createHash('sha256').update(token).digest('hex');
}

function createRefreshToken(userId: string, ip: string, domain: string) {
  const token = crypto.randomBytes(64).toString('hex');
  const expiresAt = new Date(Date.now() + REFRESH_TTL_DAYS * 24 * 60 * 60 * 1000);
  const tokenHash = hashToken(token);
  return RefreshToken.create({ user: userId, tokenHash, expiresAt, createdByIp: ip, domain });
}

export async function register(req: Request, res: Response) {
  const { email, password, fullName } = req.body;
  if (!email || !password || !fullName) {
    return res.status(400).json({ status: 'error', message: 'Email, password, and full name required' });
  }

  const existingUser = await User.findOne({ email: email.toLowerCase() });
  if (existingUser) {
    return res.status(400).json({ status: 'error', message: 'User already exists' });
  }

  const passwordHash = await bcrypt.hash(password, 12);
  const user = await User.create({
    email: email.toLowerCase(),
    passwordHash,
    fullName,
    role: 'admin',
    isActive: true
  });

  const accessToken = signAccessToken({ sub: user._id, role: user.role, domain: 'admin' });
  const refresh = await createRefreshToken(user._id.toString(), req.ip, 'admin');
  const refreshTokenRaw = crypto.randomBytes(64).toString('hex');
  refresh.tokenHash = hashToken(refreshTokenRaw);
  await refresh.save();

  res.cookie('refreshToken', refreshTokenRaw, { httpOnly: true, secure: process.env.NODE_ENV === 'production', sameSite: 'lax', path: '/api/auth/refresh', maxAge: REFRESH_TTL_DAYS * 24 * 60 * 60 * 1000 });
  return res.json({ status: 'ok', data: { user: { id: user._id, email: user.email, role: user.role }, accessToken } });
}

export async function login(req: Request, res: Response) {
  const { email, password } = req.body;
  if (!email || !password) return res.status(400).json({ status: 'error', message: 'Email and password required' });
  const user = await User.findOne({ email: email.toLowerCase() });
  if (!user || !user.passwordHash) return res.status(401).json({ status: 'error', message: 'Invalid credentials' });
  const ok = await bcrypt.compare(password, user.passwordHash);
  if (!ok) return res.status(401).json({ status: 'error', message: 'Invalid credentials' });

  const accessToken = signAccessToken({ sub: user._id, role: user.role, domain: 'admin' });
  const refresh = await createRefreshToken(user._id.toString(), req.ip, 'admin');
  const refreshTokenRaw = crypto.randomBytes(64).toString('hex');
  // store the real token as hash; here we replace the generated refresh doc tokenHash with one matching raw
  refresh.tokenHash = hashToken(refreshTokenRaw);
  await refresh.save();

  res.cookie('refreshToken', refreshTokenRaw, { httpOnly: true, secure: process.env.NODE_ENV === 'production', sameSite: 'lax', path: '/api/auth/refresh', maxAge: REFRESH_TTL_DAYS * 24 * 60 * 60 * 1000 });
  return res.json({ status: 'ok', data: { user: { id: user._id, email: user.email, role: user.role }, accessToken } });
}

export async function refresh(req: Request, res: Response) {
  const token = req.cookies?.refreshToken || req.headers['x-refresh-token'];
  if (!token) return res.status(401).json({ status: 'error', message: 'No refresh token' });
  const tokenHash = hashToken(token);
  const stored = await RefreshToken.findOne({ tokenHash, domain: 'admin' });
  if (!stored || !stored.isActive()) return res.status(401).json({ status: 'error', message: 'Invalid refresh token' });

  // Populate user based on domain
  const user = await User.findById(stored.user);
  if (!user || !user.isActive) return res.status(401).json({ status: 'error', message: 'User not found or inactive' });

  // rotate: revoke current and issue new
  stored.revokedAt = new Date();
  stored.revokedByIp = req.ip;
  const newRefreshRaw = crypto.randomBytes(64).toString('hex');
  const newHash = hashToken(newRefreshRaw);
  const newTokenDoc = await RefreshToken.create({ user: user._id, tokenHash: newHash, expiresAt: new Date(Date.now() + REFRESH_TTL_DAYS * 24 * 60 * 60 * 1000), createdByIp: req.ip, domain: 'admin' });
  stored.replacedByToken = newTokenDoc._id.toString();
  await stored.save();

  const accessToken = signAccessToken({ sub: user._id, role: user.role, domain: 'admin' });
  res.cookie('refreshToken', newRefreshRaw, { httpOnly: true, secure: process.env.NODE_ENV === 'production', sameSite: 'lax', path: '/api/auth/refresh', maxAge: REFRESH_TTL_DAYS * 24 * 60 * 60 * 1000 });
  return res.json({ status: 'ok', data: { accessToken } });
}

export async function logout(req: Request, res: Response) {
  const token = req.cookies?.refreshToken || req.headers['x-refresh-token'];
  if (token) {
    const tokenHash = hashToken(token);
    const stored = await RefreshToken.findOne({ tokenHash });
    if (stored) {
      stored.revokedAt = new Date();
      stored.revokedByIp = req.ip;
      await stored.save();
    }
  }
  res.clearCookie('refreshToken', { path: '/api/auth/refresh' });
  return res.json({ status: 'ok' });
}

export async function me(req: Request, res: Response) {
  const user = (req as any).user;
  if (!user) return res.status(401).json({ status: 'error', message: 'Unauthorized' });
  return res.json({ status: 'ok', data: user });
}
