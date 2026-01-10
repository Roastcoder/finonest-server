import mongoose, { Document, Schema } from 'mongoose';
import { PUBLISH_STATUS } from './types';

export interface ISection extends Document {
  name: string;
  type: string;
  props: Record<string, any>;
  status: typeof PUBLISH_STATUS[keyof typeof PUBLISH_STATUS];
}

const SectionSchema = new Schema<ISection>({
  name: { type: String, required: true },
  type: { type: String, required: true },
  props: { type: Schema.Types.Mixed, default: {} },
  status: { type: String, enum: Object.values(PUBLISH_STATUS), default: PUBLISH_STATUS.PUBLISHED }
}, { timestamps: true });

SectionSchema.index({ name: 1 });

export default mongoose.models.Section || mongoose.model<ISection>('Section', SectionSchema);
