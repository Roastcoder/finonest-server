import { Router } from 'express';
import { 
  sendOTPToCustomer, 
  verifyOTPAndLogin, 
  refreshCustomerToken, 
  logoutCustomer, 
  getCustomerProfile 
} from '../controllers/customer-auth.controller';
import { attachCustomer, requireCustomerAuth } from '../middleware/customer-auth';
import { validateBody } from '../middleware/validation';
import { sendOTPSchema, verifyOTPSchema } from '../validators/customer-auth.validator';

const router = Router();

// Public routes
router.post('/send-otp', validateBody(sendOTPSchema), sendOTPToCustomer);
router.post('/verify-otp', validateBody(verifyOTPSchema), verifyOTPAndLogin);
router.post('/refresh', refreshCustomerToken);
router.post('/logout', logoutCustomer);

// Protected routes
router.get('/me', attachCustomer, requireCustomerAuth, getCustomerProfile);

export default router;
