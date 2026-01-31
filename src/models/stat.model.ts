import mongoose, { Document, Schema } from 'mongoose';

export interface IStat extends Document {
  label: string;
  value: string; // Display value (e.g., "5.8 Lacs")
  suffix?: string; // Optional suffix (e.g., "+", "Cr+")
  icon?: string; // Icon identifier or name
  order?: number; // For ordering
  published?: boolean;
  publishedAt?: Date;
}

const StatSchema = new Schema<IStat>({
  label: { type: String, required: true },
  value: { type: String, required: true },
  suffix: { type: String },
  icon: { type: String },
  order: { type: Number, default: 0 },
  published: { type: Boolean, default: true },
  publishedAt: { type: Date, default: Date.now }
}, { timestamps: true });

StatSchema.index({ order: 1 });
StatSchema.index({ published: 1, publishedAt: -1 });

StatSchema.set('toJSON', {
  transform: (_doc: any, ret: any) => {
    delete ret.__v;
    ret.id = ret._id;
    delete ret._id;
    return ret;
  }
});

export default mongoose.models.Stat || mongoose.model<IStat>('Stat', StatSchema);
