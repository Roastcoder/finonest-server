import mongoose from 'mongoose';
import Page from '../src/models/page.model';

const MONGO_URI = process.env.MONGO_URI || 'mongodb://localhost:27017/finonest-dev';

async function addMissingBlocks() {
  await mongoose.connect(MONGO_URI, {} as any);

  const homePage = await Page.findOne({ slug: '/' });
  if (!homePage) {
    console.log('Home page not found');
    return;
  }

  // Add stats block after hero
  const statsBlock = {
    type: 'stats',
    props: {
      title: 'Our Achievements',
      subtitle: 'Numbers that speak for our success',
      stats: [
        { label: 'Customers Annually', value: '5.8 Lacs +', icon: '👥' },
        { label: 'Cities Covered', value: '150 +', icon: '🏙️' },
        { label: 'Branches', value: '587 +', icon: '🏢' },
        { label: 'Disbursed Annually', value: '61,000 Cr+', icon: '💰' }
      ]
    }
  };

  // Add featured services block after stats
  const featuredServicesBlock = {
    type: 'featuredServices',
    props: {
      title: 'Featured Services',
      subtitle: 'Our most popular loan products',
      services: [
        { title: 'Home Loan', description: 'Turn your dream home into reality', featured: true, image: 'https://images.unsplash.com/photo-1560518883-ce09059eeffa?w=400' },
        { title: 'Credit Card', description: 'Choose cards from all top banks', featured: true, image: 'https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?w=400' },
        { title: 'New Car Loan', description: 'Drive away your dream car today', featured: true, image: 'https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?w=400' },
        { title: 'Personal Loan', description: 'Paperless process at low rate', featured: true, image: 'https://images.unsplash.com/photo-1554224155-6726b3ff858f?w=400' }
      ]
    }
  };

  // Insert blocks at appropriate positions
  homePage.blocks.splice(1, 0, statsBlock); // After hero
  homePage.blocks.splice(2, 0, featuredServicesBlock); // After stats

  await homePage.save();
  console.log('Added missing blocks to home page');
  await mongoose.disconnect();
}

addMissingBlocks().catch(err => {
  console.error('Error adding blocks:', err);
  process.exit(1);
});