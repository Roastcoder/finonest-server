import mongoose from 'mongoose';
import Page from '../src/models/page.model';

const MONGO_URI = process.env.MONGO_URI || 'mongodb://localhost:27017/finonest-dev';

async function addCreditCardBanner() {
  await mongoose.connect(MONGO_URI, {} as any);

  const homePage = await Page.findOne({ slug: '/' });
  if (!homePage) {
    console.log('Home page not found');
    return;
  }

  const creditCardBanner = {
    type: 'creditCardBanner',
    props: {
      title: 'Your Rewards Unlimited',
      subtitle: 'Choose cards from all top banks',
      description: 'Compare and apply for credit cards from leading banks',
      backgroundImage: 'https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?w=800&q=80',
      ctaText: 'Apply Now',
      ctaLink: '/credit-cards'
    }
  };

  // Add after hero block
  homePage.blocks.splice(1, 0, creditCardBanner);
  await homePage.save();
  
  console.log('Added credit card banner block');
  await mongoose.disconnect();
}

addCreditCardBanner().catch(err => {
  console.error('Error:', err);
  process.exit(1);
});