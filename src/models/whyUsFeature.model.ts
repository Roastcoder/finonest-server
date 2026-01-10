import mongoose, { Document, Schema } from 'mongoose';

export interface IWhyUsFeature extends Document {
  title: string;
  description?: string;
  icon?: string; // Icon identifier or name
  image?: mongoose.Types.ObjectId; // Media reference for feature image
  order: number; // For ordering features
  published?: boolean;
  publishedAt?: Date;
}

const WhyUsFeatureSchema = new Schema<IWhyUsFeature>({
  title: { type: String, required: true },
  description: { type: String },
  icon: { type: String },
  image: { type: Schema.Types.ObjectId, ref: 'Media' },
  order: { type: Number, required: true, default: 0 },
  published: { type: Boolean, default: true },
  publishedAt: { type: Date, default: Date.now }
}, { timestamps: true });

WhyUsFeatureSchema.index({ order: 1 });
WhyUsFeatureSchema.index({ published: 1, publishedAt: -1 });

WhyUsFeatureSchema.set('toJSON', {
  transform: (_doc: any, ret: any) => {
    delete ret.__v;
    ret.id = ret._id;
    delete ret._id;
    return ret;
  }
});

export default mongoose.models.WhyUsFeature || mongoose.model<IWhyUsFeature>('WhyUsFeature', WhyUsFeatureSchema);
