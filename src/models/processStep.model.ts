import mongoose, { Document, Schema } from 'mongoose';

export interface IProcessStep extends Document {
  title: string;
  description?: string;
  icon?: string; // Icon identifier or name
  order: number; // For ordering steps
  published?: boolean;
  publishedAt?: Date;
}

const ProcessStepSchema = new Schema<IProcessStep>({
  title: { type: String, required: true },
  description: { type: String },
  icon: { type: String },
  order: { type: Number, required: true, default: 0 },
  published: { type: Boolean, default: true },
  publishedAt: { type: Date, default: Date.now }
}, { timestamps: true });

ProcessStepSchema.index({ order: 1 });
ProcessStepSchema.index({ published: 1, publishedAt: -1 });

ProcessStepSchema.set('toJSON', {
  transform: (_doc: any, ret: any) => {
    delete ret.__v;
    ret.id = ret._id;
    delete ret._id;
    return ret;
  }
});

export default mongoose.models.ProcessStep || mongoose.model<IProcessStep>('ProcessStep', ProcessStepSchema);
