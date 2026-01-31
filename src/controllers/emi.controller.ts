import { Request, Response } from 'express';

interface EMICalculationRequest {
  principal: number; // Loan amount
  rate: number; // Annual interest rate (percentage)
  tenureMonths: number; // Loan tenure in months
}

interface EMIScheduleEntry {
  month: number;
  principal: number;
  interest: number;
  total: number;
  remainingBalance: number;
}

interface EMICalculationResponse {
  monthlyPayment: number;
  totalAmount: number;
  totalInterest: number;
  schedule: EMIScheduleEntry[];
}

/**
 * Calculate EMI
 * POST /api/emi/calculate
 */
export async function calculateEMI(req: Request, res: Response) {
  try {
    const { principal, rate, tenureMonths }: EMICalculationRequest = req.body;

    // Validation
    if (!principal || principal <= 0) {
      return res.status(400).json({ status: 'error', message: 'Principal amount must be greater than 0' });
    }
    if (!rate || rate < 0 || rate > 100) {
      return res.status(400).json({ status: 'error', message: 'Interest rate must be between 0 and 100' });
    }
    if (!tenureMonths || tenureMonths <= 0 || tenureMonths > 600) {
      return res.status(400).json({ status: 'error', message: 'Tenure must be between 1 and 600 months' });
    }

    // Convert annual rate to monthly rate (decimal)
    const monthlyRate = (rate / 100) / 12;

    // EMI Formula: P × r × (1 + r)^n / ((1 + r)^n - 1)
    const emi = principal * monthlyRate * Math.pow(1 + monthlyRate, tenureMonths) / 
                (Math.pow(1 + monthlyRate, tenureMonths) - 1);

    const monthlyPayment = Math.round(emi * 100) / 100; // Round to 2 decimal places
    const totalAmount = monthlyPayment * tenureMonths;
    const totalInterest = totalAmount - principal;

    // Generate amortization schedule
    const schedule: EMIScheduleEntry[] = [];
    let remainingBalance = principal;

    for (let month = 1; month <= tenureMonths; month++) {
      const interest = remainingBalance * monthlyRate;
      const principalPayment = monthlyPayment - interest;
      remainingBalance = remainingBalance - principalPayment;

      schedule.push({
        month,
        principal: Math.round(principalPayment * 100) / 100,
        interest: Math.round(interest * 100) / 100,
        total: monthlyPayment,
        remainingBalance: Math.round(Math.max(0, remainingBalance) * 100) / 100
      });
    }

    const response: EMICalculationResponse = {
      monthlyPayment,
      totalAmount: Math.round(totalAmount * 100) / 100,
      totalInterest: Math.round(totalInterest * 100) / 100,
      schedule
    };

    return res.json({ status: 'ok', data: response });
  } catch (err: any) {
    return res.status(500).json({ status: 'error', message: 'Server error', details: err.message });
  }
}
