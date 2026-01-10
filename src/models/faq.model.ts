import mongoose, { Document, Schema } from 'mongoose';

export interface IFAQ extends Document {
  question: string;
  answer: string;
  category?: string; // e.g., 'eligibility', 'documents', 'interest-rates'
  order?: number; // For ordering within category
  serviceRef?: mongoose.Types.ObjectId; // Optional: link to specific service
  published?: boolean;
  publishedAt?: Date;
}

const FAQSchema = new Schema<IFAQ>({
  question: { type: String, required: true },
  answer: { type: String, required: true },
  category: { type: String, index: true },
  order: { type: Number, default: 0 },
  serviceRef: { type: Schema.Types.ObjectId, ref: 'Service' },
  published: { type: Boolean, default: true },
  publishedAt: { type: Date, default: Date.now }
}, { timestamps: true });

FAQSchema.index({ category: 1, order: 1 });
FAQSchema.index({ published: 1, publishedAt: -1 });
FAQSchema.index({ serviceRef: 1 });

FAQSchema.set('toJSON', {
  transform: (_doc: any, ret: any) => {
    delete ret.__v;
    ret.id = ret._id;
    delete ret._id;
    return ret;
  }
});

export default mongoose.models.FAQ || mongoose.model<IFAQ>('FAQ', FAQSchema);
