import mongoose, { Document, Schema } from 'mongoose';

export interface IFormField {
  name: string;
  label: string;
  type: string; // text, email, select, file, number, date
  required?: boolean;
  validation?: Record<string, any>;
  options?: { label: string; value: any }[];
}

export interface IFormConfig extends Document {
  name: string;
  slug: string;
  fields: IFormField[];
  steps?: { title: string; fields: string[] }[];
  recipientEmails?: string[];
  webhookUrl?: string;
  autoReplyTemplate?: string;
  storeInDB?: boolean;
}

const FieldSchema = new Schema<IFormField>({
  name: { type: String, required: true },
  label: { type: String, required: true },
  type: { type: String, required: true },
  required: { type: Boolean, default: false },
  validation: { type: Schema.Types.Mixed, default: {} },
  options: [{ label: String, value: Schema.Types.Mixed }]
}, { _id: false });

const FormSchema = new Schema<IFormConfig>({
  name: { type: String, required: true },
  slug: { type: String, required: true, unique: true, index: true },
  fields: { type: [FieldSchema], default: [] },
  steps: { type: [Schema.Types.Mixed], default: [] },
  recipientEmails: { type: [String], default: [] },
  webhookUrl: { type: String },
  autoReplyTemplate: { type: String },
  storeInDB: { type: Boolean, default: true }
}, { timestamps: true });

FormSchema.index({ slug: 1 });

export default mongoose.models.FormConfig || mongoose.model<IFormConfig>('FormConfig', FormSchema);
