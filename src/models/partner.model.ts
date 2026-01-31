import mongoose, { Document, Schema } from 'mongoose';

export interface IPartnerBank extends Document {
  name: string;
  slug: string;
  logo?: mongoose.Types.ObjectId;
  description?: string;
  featured?: boolean;
  applyLink?: string;
  seo?: { metaTitle?: string; metaDescription?: string; canonical?: string };
  status?: 'published' | 'draft' | string;
}

const PartnerSchema = new Schema<IPartnerBank>({
  name: { type: String, required: true },
  slug: { type: String, required: true, unique: true, index: true },
  logo: { type: Schema.Types.ObjectId, ref: 'Media' },
  description: { type: String },
  featured: { type: Boolean, default: false },
  applyLink: { type: String },
  seo: { metaTitle: String, metaDescription: String, canonical: String },
  status: { type: String, default: 'published' }
}, { timestamps: true });

PartnerSchema.index({ featured: -1, name: 1 });

PartnerSchema.set('toJSON', {
  transform: (_doc: any, ret: any) => {
    delete ret.__v;
    ret.id = ret._id;
    delete ret._id;
    return ret;
  }
});

export default mongoose.models.Partner || mongoose.model<IPartnerBank>('Partner', PartnerSchema);
