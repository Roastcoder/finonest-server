import { Request, Response } from 'express';
import fs from 'fs';
import path from 'path';
import sharp from 'sharp';
import Media from '../models/media.model';

const UPLOAD_DIR = path.join(process.cwd(), 'public', 'assets', 'uploads');

function ensureUploadDir() {
  if (!fs.existsSync(UPLOAD_DIR)) fs.mkdirSync(UPLOAD_DIR, { recursive: true });
}

export async function uploadMedia(req: Request, res: Response) {
  try {
    ensureUploadDir();
    if (!req.file) return res.status(400).json({ status: 'error', message: 'No file uploaded' });
    const { originalname, mimetype, size, filename, path: filepath } = req.file as any;
    const ext = path.extname(originalname).toLowerCase();

    let width: number | undefined;
    let height: number | undefined;
    try {
      const metadata = await sharp(filepath).metadata();
      width = metadata.width || undefined;
      height = metadata.height || undefined;
    } catch (e) {
      // not an image or sharp failed, ignore
    }

    const url = `/assets/uploads/${filename}`;
    const doc = await Media.create({ filename: originalname, url, mimeType: mimetype, size, width, height, altText: req.body.altText || '', caption: req.body.caption || '', tags: req.body.tags ? JSON.parse(req.body.tags) : [], uploadedBy: (req as any).user?._id });
    return res.status(201).json({ status: 'ok', data: doc });
  } catch (err: any) {
    return res.status(500).json({ status: 'error', message: err.message });
  }
}

export async function listMediaPublic(req: Request, res: Response) {
  const page = parseInt((req.query.page as string) || '1', 10);
  const limit = Math.min(parseInt((req.query.limit as string) || '20', 10), 100);
  try {
    const items = await Media.find({}).skip((page - 1) * limit).limit(limit).sort({ createdAt: -1 }).lean();
    const total = await Media.countDocuments({});
    return res.json({ status: 'ok', data: { items, total, page, limit } });
  } catch (err) {
    return res.status(500).json({ status: 'error', message: 'Server error' });
  }
}

export async function getMediaById(req: Request, res: Response) {
  try {
    const item = await Media.findById(req.params.id).lean();
    if (!item) return res.status(404).json({ status: 'error', message: 'Not found' });
    return res.json({ status: 'ok', data: item });
  } catch (err) {
    return res.status(500).json({ status: 'error', message: 'Server error' });
  }
}
