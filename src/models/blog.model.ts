import mongoose, { Document, Schema } from 'mongoose';
import { PUBLISH_STATUS } from './types';

export interface IBlogPost extends Document {
  slug: string;
  title: string;
  excerpt?: string;
  content: string;
  featuredImage?: mongoose.Types.ObjectId;
  author?: mongoose.Types.ObjectId;
  categories: mongoose.Types.ObjectId[];
  tags: string[];
  status: typeof PUBLISH_STATUS[keyof typeof PUBLISH_STATUS];
  publishedAt?: Date;
  readTime?: string;
  seo?: {
    metaTitle?: string;
    metaDescription?: string;
    canonical?: string;
    openGraphImage?: mongoose.Types.ObjectId;
  };
  metrics?: {
    views?: number;
    likes?: number;
  };
}

const BlogPostSchema = new Schema<IBlogPost>({
  slug: { type: String, required: true, unique: true, index: true },
  title: { type: String, required: true },
  excerpt: { type: String },
  content: { type: String, required: true },
  featuredImage: { type: Schema.Types.ObjectId, ref: 'Media' },
  author: { type: Schema.Types.ObjectId, ref: 'User' },
  categories: [{ type: Schema.Types.ObjectId, ref: 'Category' }],
  tags: [{ type: String }],
  status: { type: String, enum: Object.values(PUBLISH_STATUS), default: PUBLISH_STATUS.DRAFT },
  publishedAt: { type: Date },
  readTime: { type: String },
  seo: {
    metaTitle: String,
    metaDescription: String,
    canonical: String,
    openGraphImage: { type: Schema.Types.ObjectId, ref: 'Media' }
  },
  metrics: { views: { type: Number, default: 0 }, likes: { type: Number, default: 0 } }
}, { timestamps: true });

BlogPostSchema.index({ slug: 1 });
BlogPostSchema.index({ publishedAt: -1 });

BlogPostSchema.set('toJSON', {
  transform: (_doc: any, ret: any) => {
    delete ret.__v;
    ret.id = ret._id;
    delete ret._id;
    return ret;
  }
});

export default mongoose.models.BlogPost || mongoose.model<IBlogPost>('BlogPost', BlogPostSchema);