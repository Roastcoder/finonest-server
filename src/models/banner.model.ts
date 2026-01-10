import mongoose, { Document, Schema } from 'mongoose';

export interface IBanner extends Document {
  title?: string;
  image?: mongoose.Types.ObjectId;
  link?: string;
  priority?: number;
  startDate?: Date;
  endDate?: Date;
  active?: boolean;
}

const BannerSchema = new Schema<IBanner>({
  title: { type: String },
  image: { type: Schema.Types.ObjectId, ref: 'Media' },
  link: { type: String },
  priority: { type: Number, default: 0 },
  startDate: { type: Date },
  endDate: { type: Date },
  active: { type: Boolean, default: true }
}, { timestamps: true });

BannerSchema.index({ priority: -1, active: 1 });

export default mongoose.models.Banner || mongoose.model<IBanner>('Banner', BannerSchema);
