import { Router } from 'express';
import multer from 'multer';
import path from 'path';
import { uploadMedia, listMediaPublic, getMediaById } from '../controllers/media.controller';
import { requireAuth } from '../middleware/auth';
import { requireRole } from '../middleware/roles';

const router = Router();

const storage = multer.diskStorage({
  destination: (req, file, cb) => {
    cb(null, path.join(process.cwd(), 'public', 'assets', 'uploads'));
  },
  filename: (req, file, cb) => {
    const ext = path.extname(file.originalname);
    const filename = `${Date.now()}-${Math.random().toString(36).slice(2, 8)}${ext}`;
    cb(null, filename);
  }
});

const upload = multer({ storage, limits: { fileSize: 5 * 1024 * 1024 } }); // 5MB limit

// Public
router.get('/media', listMediaPublic);
router.get('/media/:id', getMediaById);

// Admin upload
router.post('/media/upload', requireAuth, requireRole('editor', 'admin', 'superadmin'), upload.single('file'), uploadMedia);

export default router;
