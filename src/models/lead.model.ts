import mongoose, { Document, Schema } from 'mongoose';

export interface ILead extends Document {
  formRef?: mongoose.Types.ObjectId;
  customer?: mongoose.Types.ObjectId; // Link to Customer account
  data: Record<string, any>;
  files?: mongoose.Types.ObjectId[];
  ip?: string;
  source?: string;
  assignedTo?: mongoose.Types.ObjectId;
  status?: 'new' | 'contacted' | 'qualified' | 'lost' | 'approved' | 'rejected' | string;
  loanType?: string;
  loanAmount?: number;
  applicationNumber?: string; // Unique application reference
}

const LeadSchema = new Schema<ILead>({
  formRef: { type: Schema.Types.ObjectId, ref: 'FormConfig' },
  customer: { type: Schema.Types.ObjectId, ref: 'Customer', index: true },
  data: { type: Schema.Types.Mixed, required: true },
  files: [{ type: Schema.Types.ObjectId, ref: 'Media' }],
  ip: { type: String },
  source: { type: String },
  assignedTo: { type: Schema.Types.ObjectId, ref: 'User' },
  status: { type: String, default: 'new', index: true },
  loanType: { type: String, index: true },
  loanAmount: { type: Number },
  applicationNumber: { type: String, unique: true, sparse: true, index: true }
}, { timestamps: true });

LeadSchema.index({ createdAt: -1 });

export default mongoose.models.Lead || mongoose.model<ILead>('Lead', LeadSchema);
