import mongoose, { Document, Schema } from 'mongoose';
import { USER_ROLES } from './types';

export interface IUser extends Document {
  email: string;
  name?: string;
  passwordHash?: string;
  role: typeof USER_ROLES[keyof typeof USER_ROLES];
  permissions: string[];
  isActive: boolean;
}

const UserSchema = new Schema<IUser>({
  email: { type: String, required: true, unique: true, lowercase: true, trim: true },
  name: { type: String },
  passwordHash: { type: String },
  role: { type: String, required: true, enum: Object.values(USER_ROLES), default: USER_ROLES.CONTRIBUTOR },
  permissions: { type: [String], default: [] },
  isActive: { type: Boolean, default: true },
}, { timestamps: true });

UserSchema.index({ email: 1 });

export default mongoose.models.User || mongoose.model<IUser>('User', UserSchema);
