import mongoose, { Document, Schema } from 'mongoose';

export interface ISiteSettings extends Document {
  siteName: string;
  siteTagline?: string;
  primaryPhone?: string;
  primaryEmail?: string;
  address?: string;
  whatsappNumber?: string;
  facebookUrl?: string;
  instagramUrl?: string;
  linkedinUrl?: string;
  twitterUrl?: string;
  youtubeUrl?: string;
  metaTitle?: string;
  metaDescription?: string;
  googleAnalyticsId?: string;
  maintenanceMode?: boolean;
  // Legacy fields for backward compatibility
  siteTitle?: string;
  siteDescription?: string;
  logo?: mongoose.Types.ObjectId;
  favicon?: mongoose.Types.ObjectId;
  socialLinks?: { facebook?: string; twitter?: string; linkedin?: string; instagram?: string };
  contactInfo?: { phone?: string; email?: string; address?: string };
  footerContent?: string;
  analytics?: { gaTrackingId?: string };
}

const SettingsSchema = new Schema<ISiteSettings>({
  // New fields
  siteName: { type: String, default: 'Finonest' },
  siteTagline: { type: String },
  primaryPhone: { type: String },
  primaryEmail: { type: String },
  address: { type: String },
  whatsappNumber: { type: String },
  facebookUrl: { type: String },
  instagramUrl: { type: String },
  linkedinUrl: { type: String },
  twitterUrl: { type: String },
  youtubeUrl: { type: String },
  metaTitle: { type: String },
  metaDescription: { type: String },
  googleAnalyticsId: { type: String },
  maintenanceMode: { type: Boolean, default: false },
  // Legacy fields
  siteTitle: { type: String },
  siteDescription: { type: String },
  logo: { type: Schema.Types.ObjectId, ref: 'Media' },
  favicon: { type: Schema.Types.ObjectId, ref: 'Media' },
  socialLinks: { type: Schema.Types.Mixed, default: {} },
  contactInfo: { type: Schema.Types.Mixed, default: {} },
  footerContent: { type: String },
  analytics: { type: Schema.Types.Mixed, default: {} }
}, { timestamps: true });

// Singleton helper: ensure single settings doc usage is enforced at service layer
SettingsSchema.index({ siteTitle: 1 });

SettingsSchema.set('toJSON', {
  transform: (_doc: any, ret: any) => {
    delete ret.__v;
    ret.id = ret._id;
    delete ret._id;
    return ret;
  }
});

export default mongoose.models.SiteSettings || mongoose.model<ISiteSettings>('SiteSettings', SettingsSchema);
