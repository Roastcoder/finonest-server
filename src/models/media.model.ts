import mongoose, { Document, Schema } from 'mongoose';

export interface IMedia extends Document {
  filename: string;
  url: string;
  mimeType: string;
  size: number;
  width?: number;
  height?: number;
  altText?: string;
  caption?: string;
  tags?: string[];
  uploadedBy?: mongoose.Types.ObjectId;
}

const MediaSchema = new Schema<IMedia>({
  filename: { type: String, required: true },
  url: { type: String, required: true },
  mimeType: { type: String, required: true },
  size: { type: Number, required: true },
  width: { type: Number },
  height: { type: Number },
  altText: { type: String },
  caption: { type: String },
  tags: { type: [String], default: [] },
  uploadedBy: { type: Schema.Types.ObjectId, ref: 'User' },
}, { timestamps: true });

MediaSchema.index({ tags: 1 });

export default mongoose.models.Media || mongoose.model<IMedia>('Media', MediaSchema);
