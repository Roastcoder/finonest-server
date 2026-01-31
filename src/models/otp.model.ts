import mongoose, { Document, Schema } from 'mongoose';

export interface IOTP extends Document {
  phone: string;
  code: string; // Hashed OTP code
  purpose: 'login' | 'verify' | 'reset';
  expiresAt: Date;
  attempts: number;
  verified: boolean;
  verifiedAt?: Date;
  ip?: string;
}

const OTPSchema = new Schema<IOTP>({
  phone: { type: String, required: true, index: true },
  code: { type: String, required: true }, // Stored as hash
  purpose: { type: String, enum: ['login', 'verify', 'reset'], default: 'login' },
  expiresAt: { type: Date, required: true, index: { expireAfterSeconds: 0 } },
  attempts: { type: Number, default: 0 },
  verified: { type: Boolean, default: false },
  verifiedAt: Date,
  ip: String,
}, { timestamps: true });

// TTL index for automatic cleanup of expired OTPs
OTPSchema.index({ expiresAt: 1 }, { expireAfterSeconds: 0 });
OTPSchema.index({ phone: 1, purpose: 1, verified: 1 });

export default mongoose.models.OTP || mongoose.model<IOTP>('OTP', OTPSchema);
