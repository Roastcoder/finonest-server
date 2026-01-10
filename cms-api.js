import express from 'express';
import mongoose from 'mongoose';
import cors from 'cors';

const app = express();
app.use(cors());
app.use(express.json());

mongoose.connect('mongodb://localhost:27017/finonest');

const pageSchema = new mongoose.Schema({
  slug: String,
  title: String,
  template: String,
  blocks: Array,
  status: String,
  seo: Object
}, { timestamps: true });

const Page = mongoose.model('Page', pageSchema);

// Public API - Get page by slug
app.get('/api/pages/:slug', async (req, res) => {
  try {
    const page = await Page.findOne({ slug: `/${req.params.slug}` });
    if (!page) {
      return res.status(404).json({ status: 'error', message: 'Page not found' });
    }
    res.json({ status: 'ok', data: page });
  } catch (error) {
    res.status(500).json({ status: 'error', message: error.message });
  }
});

// Admin API - List all pages
app.get('/api/admin/pages', async (req, res) => {
  try {
    const pages = await Page.find({});
    res.json({ status: 'ok', data: pages });
  } catch (error) {
    res.status(500).json({ status: 'error', message: error.message });
  }
});

// Admin API - Update page
app.patch('/api/admin/pages/:id', async (req, res) => {
  try {
    const page = await Page.findByIdAndUpdate(req.params.id, req.body, { new: true });
    res.json({ status: 'ok', data: page });
  } catch (error) {
    res.status(500).json({ status: 'error', message: error.message });
  }
});

app.listen(4000, () => {
  console.log('CMS API server running on http://localhost:4000');
});