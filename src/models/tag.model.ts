import mongoose, { Document, Schema } from 'mongoose';

export interface ITag extends Document {
  name: string;
  slug: string;
}

const TagSchema = new Schema<ITag>({
  name: { type: String, required: true },
  slug: { type: String, required: true, unique: true },
}, { timestamps: true });

export default mongoose.models.Tag || mongoose.model<ITag>('Tag', TagSchema);
