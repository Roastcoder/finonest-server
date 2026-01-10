import mongoose, { Document, Schema } from 'mongoose';
import { AUTH_DOMAIN } from './types';

export interface IRefreshToken extends Document {
  user: mongoose.Types.ObjectId; // Can reference User or Customer
  tokenHash: string;
  expiresAt: Date;
  createdAt: Date;
  createdByIp?: string;
  revokedAt?: Date | null;
  revokedByIp?: string | null;
  replacedByToken?: string | null;
  domain?: typeof AUTH_DOMAIN[keyof typeof AUTH_DOMAIN]; // 'admin' or 'customer'
}

const RefreshTokenSchema = new Schema<IRefreshToken>({
  user: { 
    type: Schema.Types.ObjectId, 
    required: true,
    // Will be populated based on domain field - no refPath needed
  },
  tokenHash: { type: String, required: true, index: true },
  expiresAt: { type: Date, required: true },
  createdAt: { type: Date, default: Date.now },
  createdByIp: { type: String },
  revokedAt: { type: Date, default: null },
  revokedByIp: { type: String, default: null },
  replacedByToken: { type: String, default: null },
  domain: { type: String, enum: Object.values(AUTH_DOMAIN), default: AUTH_DOMAIN.ADMIN, index: true }
});

RefreshTokenSchema.index({ tokenHash: 1, domain: 1 });

RefreshTokenSchema.methods.isActive = function () {
  return !this.revokedAt && this.expiresAt > new Date();
};

export default mongoose.models.RefreshToken || mongoose.model<IRefreshToken>('RefreshToken', RefreshTokenSchema);
