import mongoose from 'mongoose';
import Page from '../src/models/page.model';

const MONGO_URI = process.env.MONGO_URI || 'mongodb://localhost:27017/finonest-dev';

async function cleanupDuplicatePages() {
  await mongoose.connect(MONGO_URI, {} as any);

  // Remove duplicate home pages - keep only the one with slug '/'
  await Page.deleteMany({ 
    slug: { $in: ['home', 'about'] },
    title: { $in: ['Home - Finonest', 'About Us - Finonest'] }
  });

  console.log('Cleaned up duplicate pages');
  await mongoose.disconnect();
}

cleanupDuplicatePages().catch(err => {
  console.error('Error cleaning up pages:', err);
  process.exit(1);
});