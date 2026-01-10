import mongoose, { Document, Schema } from 'mongoose';

export interface ICustomer extends Document {
  phone: string;
  email?: string;
  name?: string;
  isVerified: boolean;
  isActive: boolean;
  profile?: {
    dateOfBirth?: Date;
    address?: string;
    city?: string;
    state?: string;
    pincode?: string;
    panNumber?: string;
    aadhaarNumber?: string;
  };
  preferences?: {
    notifications?: boolean;
    marketing?: boolean;
  };
  lastLoginAt?: Date;
}

const CustomerSchema = new Schema<ICustomer>({
  phone: { type: String, required: true, unique: true, index: true, trim: true },
  email: { type: String, lowercase: true, trim: true, sparse: true },
  name: { type: String, trim: true },
  isVerified: { type: Boolean, default: false },
  isActive: { type: Boolean, default: true },
  profile: {
    dateOfBirth: Date,
    address: String,
    city: String,
    state: String,
    pincode: String,
    panNumber: String,
    aadhaarNumber: String,
  },
  preferences: {
    notifications: { type: Boolean, default: true },
    marketing: { type: Boolean, default: false },
  },
  lastLoginAt: Date,
}, { timestamps: true });

CustomerSchema.index({ phone: 1 });
CustomerSchema.index({ email: 1 }, { sparse: true });

CustomerSchema.set('toJSON', {
  transform: (_doc, ret) => {
    delete ret.__v;
    ret.id = ret._id;
    delete ret._id;
    return ret;
  }
});

export default mongoose.models.Customer || mongoose.model<ICustomer>('Customer', CustomerSchema);
