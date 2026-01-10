import mongoose, { Document, Schema } from 'mongoose';

export interface INavItem extends Document {
  label: string;
  href: string; // Internal route or external URL
  position: 'header' | 'footer' | 'both';
  order: number; // For ordering within position
  parentNavId?: mongoose.Types.ObjectId; // For nested menus
  icon?: string; // Optional icon identifier
  target?: '_self' | '_blank'; // Link target
  published?: boolean;
  publishedAt?: Date;
}

const NavItemSchema = new Schema<INavItem>({
  label: { type: String, required: true },
  href: { type: String, required: true },
  position: { type: String, enum: ['header', 'footer', 'both'], default: 'header', index: true },
  order: { type: Number, default: 0 },
  parentNavId: { type: Schema.Types.ObjectId, ref: 'NavItem' },
  icon: { type: String },
  target: { type: String, enum: ['_self', '_blank'], default: '_self' },
  published: { type: Boolean, default: true },
  publishedAt: { type: Date, default: Date.now }
}, { timestamps: true });

NavItemSchema.index({ position: 1, order: 1 });
NavItemSchema.index({ published: 1, publishedAt: -1 });
NavItemSchema.index({ parentNavId: 1 });

NavItemSchema.set('toJSON', {
  transform: (_doc: any, ret: any) => {
    delete ret.__v;
    ret.id = ret._id;
    delete ret._id;
    return ret;
  }
});

export default mongoose.models.NavItem || mongoose.model<INavItem>('NavItem', NavItemSchema);
