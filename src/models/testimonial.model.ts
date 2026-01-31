import mongoose, { Document, Schema } from 'mongoose';

export interface ITestimonial extends Document {
  name: string;
  role?: string;
  content: string;
  rating?: number;
  avatar?: mongoose.Types.ObjectId;
  source?: string;
  published?: boolean;
  publishedAt?: Date;
}

const TestimonialSchema = new Schema<ITestimonial>({
  name: { type: String, required: true },
  role: { type: String },
  content: { type: String, required: true },
  rating: { type: Number, min: 1, max: 5 },
  avatar: { type: Schema.Types.ObjectId, ref: 'Media' },
  source: { type: String },
  published: { type: Boolean, default: true },
  publishedAt: { type: Date }
}, { timestamps: true });

TestimonialSchema.index({ published: 1, publishedAt: -1 });

TestimonialSchema.set('toJSON', {
  transform: (_doc, ret) => {
    delete ret.__v;
    ret.id = ret._id;
    delete ret._id;
    return ret;
  }
});

export default mongoose.models.Testimonial || mongoose.model<ITestimonial>('Testimonial', TestimonialSchema);
