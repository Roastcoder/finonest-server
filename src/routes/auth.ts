import { Router } from 'express';
import { login, register, refresh, logout, me } from '../controllers/auth.controller';
import { attachUser, requireAuth } from '../middleware/auth';
import { validateBody } from '../middleware/validation';
import { loginSchema, registerSchema } from '../validators/auth.validator';

const router = Router();

router.post('/login', validateBody(loginSchema), login);
router.post('/register', validateBody(registerSchema), register);
router.post('/refresh', refresh);
router.post('/logout', logout);
router.get('/me', attachUser, requireAuth, me);

export default router;
