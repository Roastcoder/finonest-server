import mongoose, { Document, Schema } from 'mongoose';
import { PUBLISH_STATUS } from './types';

export interface IService extends Document {
  slug: string;
  title: string;
  shortDescription?: string;
  heroImage?: mongoose.Types.ObjectId;
  heroHeading?: string;
  overview?: string; // Rich text (HTML/Markdown)
  benefits?: { title: string; description?: string }[];
  features?: { title: string; description?: string }[];
  rate?: string;
  eligibility?: string[];
  requiredDocs?: string[];
  faqs?: { question: string; answer: string }[];
  partnerBanks?: mongoose.Types.ObjectId[];
  applicationFormRef?: mongoose.Types.ObjectId;
  relatedServices?: mongoose.Types.ObjectId[];
  highlight?: boolean; // Popular/featured flag
  seo?: {
    metaTitle?: string;
    metaDescription?: string;
    canonical?: string;
    openGraphImage?: mongoose.Types.ObjectId;
  };
  status: typeof PUBLISH_STATUS[keyof typeof PUBLISH_STATUS];
  publishedAt?: Date;
}

const ServiceSchema = new Schema<IService>({
  slug: { type: String, required: true, unique: true, index: true },
  title: { type: String, required: true },
  shortDescription: { type: String },
  heroImage: { type: Schema.Types.ObjectId, ref: 'Media' },
  heroHeading: { type: String },
  overview: { type: String }, // Rich text content
  benefits: [{ title: String, description: String }],
  features: [{ title: String, description: String }],
  rate: { type: String },
  eligibility: [{ type: String }],
  requiredDocs: [{ type: String }],
  faqs: [{ question: String, answer: String }],
  partnerBanks: [{ type: Schema.Types.ObjectId, ref: 'Partner' }],
  applicationFormRef: { type: Schema.Types.ObjectId, ref: 'FormConfig' },
  relatedServices: [{ type: Schema.Types.ObjectId, ref: 'Service' }],
  highlight: { type: Boolean, default: false },
  seo: {
    metaTitle: String,
    metaDescription: String,
    canonical: String,
    openGraphImage: { type: Schema.Types.ObjectId, ref: 'Media' }
  },
  status: { type: String, enum: Object.values(PUBLISH_STATUS), default: PUBLISH_STATUS.DRAFT },
  publishedAt: { type: Date }
}, { timestamps: true });

ServiceSchema.index({ slug: 1 });
ServiceSchema.index({ highlight: -1, status: 1 });

ServiceSchema.set('toJSON', {
  transform: (_doc, ret) => {
    delete ret.__v;
    ret.id = ret._id;
    delete ret._id;
    return ret;
  }
});

export default mongoose.models.Service || mongoose.model<IService>('Service', ServiceSchema);
