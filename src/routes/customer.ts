import { Router } from 'express';
import {
  getCustomerProfile,
  updateCustomerProfile,
  getCustomerApplications,
  getApplicationDetails,
  getCustomerDashboard,
} from '../controllers/customer.controller';
import { attachCustomer, requireCustomerAuth } from '../middleware/customer-auth';
import { validateBody } from '../middleware/validation';
import { customerProfileSchema } from '../validators/customer.validator';

const router = Router();

// All routes require customer authentication
router.use(attachCustomer, requireCustomerAuth);

// Profile management
router.get('/profile', getCustomerProfile);
router.patch('/profile', validateBody(customerProfileSchema), updateCustomerProfile);

// Dashboard
router.get('/dashboard', getCustomerDashboard);

// Applications
router.get('/applications', getCustomerApplications);
router.get('/applications/:id', getApplicationDetails);

export default router;
