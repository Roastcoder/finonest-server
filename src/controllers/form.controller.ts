import { Request, Response } from 'express';
import FormConfig from '../models/form.model';
import Lead from '../models/lead.model';
import Customer from '../models/customer.model';
import crypto from 'crypto';

/**
 * Generate unique application number
 */
function generateApplicationNumber(): string {
  const prefix = 'FIN';
  const timestamp = Date.now().toString().slice(-8);
  const random = crypto.randomBytes(2).toString('hex').toUpperCase();
  return `${prefix}${timestamp}${random}`;
}

/**
 * Submit form (public endpoint - can be used by authenticated customers or anonymous)
 * POST /api/forms/:slug/submit
 */
export async function submitForm(req: Request, res: Response) {
  try {
    const { slug } = req.params;
    const formData = req.body;
    const customer = (req as any).customer; // Optional - customer may be logged in

    // Find form configuration
    const formConfig = await FormConfig.findOne({ slug });
    if (!formConfig) {
      return res.status(404).json({ status: 'error', message: 'Form not found' });
    }

    // Validate form data against form config fields
    // (Basic validation - can be enhanced)
    const requiredFields = formConfig.fields.filter((f: any) => f.required);
    for (const field of requiredFields) {
      if (!formData[field.name]) {
        return res.status(400).json({ 
          status: 'error', 
          message: `Field ${field.label || field.name} is required` 
        });
      }
    }

    // Extract loan type and amount if present
    const loanType = formData.loanType || formData.loan_type || formData.service;
    const loanAmount = formData.loanAmount || formData.loan_amount || formData.amount;

    // If customer is authenticated, link the application
    let customerId = customer?._id;
    
    // If not authenticated but phone/email provided, try to find customer
    if (!customerId && (formData.phone || formData.email)) {
      const phone = formData.phone?.replace(/\D/g, '');
      if (phone) {
        const existingCustomer = await Customer.findOne({ phone });
        if (existingCustomer) {
          customerId = existingCustomer._id;
        }
      } else if (formData.email) {
        const existingCustomer = await Customer.findOne({ email: formData.email.toLowerCase() });
        if (existingCustomer) {
          customerId = existingCustomer._id;
        }
      }
    }

    // Create lead/application
    const applicationNumber = generateApplicationNumber();
    const lead = await Lead.create({
      formRef: formConfig._id,
      customer: customerId,
      data: formData,
      ip: req.ip,
      source: req.headers.referer || 'direct',
      status: 'new',
      loanType,
      loanAmount: loanAmount ? parseFloat(loanAmount) : undefined,
      applicationNumber,
    });

    // TODO: Send notifications (email, webhook, etc.)
    // TODO: Send auto-reply if configured

    return res.status(201).json({
      status: 'ok',
      data: {
        leadId: lead._id,
        applicationNumber: lead.applicationNumber,
        message: 'Application submitted successfully',
      },
    });
  } catch (err: any) {
    console.error('Form submission error:', err);
    return res.status(500).json({ status: 'error', message: 'Failed to submit form', details: err.message });
  }
}
