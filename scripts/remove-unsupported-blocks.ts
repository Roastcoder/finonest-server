import mongoose from 'mongoose';
import Page from '../src/models/page.model';

const MONGO_URI = process.env.MONGO_URI || 'mongodb://localhost:27017/finonest-dev';

async function removeUnsupportedBlocks() {
  await mongoose.connect(MONGO_URI, {} as any);

  const homePage = await Page.findOne({ slug: '/' });
  if (!homePage) {
    console.log('Home page not found');
    return;
  }

  // Remove unsupported block types
  const supportedTypes = ['hero', 'services', 'processSteps', 'whyUs', 'testimonials', 'faq', 'contact', 'partnerBanks', 'creditScoreBanner'];
  
  homePage.blocks = homePage.blocks.filter(block => supportedTypes.includes(block.type));
  
  await homePage.save();
  console.log('Removed unsupported blocks from home page');
  await mongoose.disconnect();
}

removeUnsupportedBlocks().catch(err => {
  console.error('Error:', err);
  process.exit(1);
});