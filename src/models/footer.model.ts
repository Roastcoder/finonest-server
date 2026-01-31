import mongoose, { Document, Schema } from 'mongoose';

export interface IFooter extends Document {
  quickLinks?: { label: string; href: string; target?: '_self' | '_blank' }[];
  contactInfo?: {
    phone?: string;
    email?: string;
    address?: string;
  };
  socialLinks?: {
    facebook?: string;
    twitter?: string;
    linkedin?: string;
    instagram?: string;
    youtube?: string;
  };
  legalLinks?: { label: string; href: string; target?: '_self' | '_blank' }[];
  copyrightText?: string;
  published?: boolean;
  publishedAt?: Date;
}

const FooterSchema = new Schema<IFooter>({
  quickLinks: [{
    label: { type: String, required: true },
    href: { type: String, required: true },
    target: { type: String, enum: ['_self', '_blank'], default: '_self' }
  }],
  contactInfo: {
    phone: String,
    email: String,
    address: String
  },
  socialLinks: {
    facebook: String,
    twitter: String,
    linkedin: String,
    instagram: String,
    youtube: String
  },
  legalLinks: [{
    label: { type: String, required: true },
    href: { type: String, required: true },
    target: { type: String, enum: ['_self', '_blank'], default: '_self' }
  }],
  copyrightText: { type: String },
  published: { type: Boolean, default: true },
  publishedAt: { type: Date, default: Date.now }
}, { timestamps: true });

// Singleton pattern: ensure only one footer document
FooterSchema.index({ published: 1, publishedAt: -1 });

FooterSchema.set('toJSON', {
  transform: (_doc: any, ret: any) => {
    delete ret.__v;
    ret.id = ret._id;
    delete ret._id;
    return ret;
  }
});

export default mongoose.models.Footer || mongoose.model<IFooter>('Footer', FooterSchema);
