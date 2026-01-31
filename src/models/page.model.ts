import mongoose, { Document, Schema } from 'mongoose';
import { PUBLISH_STATUS } from './types';

export interface IBlock {
  type: string;
  props: Record<string, any>;
}

export interface IPage extends Document {
  slug: string;
  title?: string;
  template?: string;
  blocks: IBlock[];
  status: typeof PUBLISH_STATUS[keyof typeof PUBLISH_STATUS];
  publishedAt?: Date;
  seo?: {
    metaTitle?: string;
    metaDescription?: string;
    canonical?: string;
    openGraphImage?: mongoose.Types.ObjectId;
  };
}

const BlockSchema = new Schema<IBlock>({
  type: { type: String, required: true },
  props: { type: Schema.Types.Mixed, default: {} },
}, { _id: false });

const PageSchema = new Schema<IPage>({
  slug: { type: String, required: true, unique: true, index: true },
  title: { type: String },
  template: { type: String, default: 'default' },
  blocks: { type: [BlockSchema], default: [] },
  status: { type: String, enum: Object.values(PUBLISH_STATUS), default: PUBLISH_STATUS.DRAFT },
  publishedAt: { type: Date },
  seo: {
    metaTitle: String,
    metaDescription: String,
    canonical: String,
    openGraphImage: { type: Schema.Types.ObjectId, ref: 'Media' }
  }
}, { timestamps: true });

PageSchema.index({ slug: 1 });

PageSchema.set('toJSON', {
  transform: (_doc, ret) => {
    delete ret.__v;
    return ret;
  }
});

export default mongoose.models.Page || mongoose.model<IPage>('Page', PageSchema);
