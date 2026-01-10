import mongoose from 'mongoose';
import SiteSettings from '../src/models/settings.model';

const MONGO_URI = process.env.MONGO_URI || 'mongodb://localhost:27017/finonest-dev';

async function seedSiteSettings() {
  await mongoose.connect(MONGO_URI, {} as any);

  const siteSettingsData = {
    siteName: 'Finonest',
    siteTagline: 'Your Trusted Loan Partner',
    primaryPhone: '+91 9876543210',
    primaryEmail: 'contact@finonest.com',
    address: '3rd Floor, Besides Jaipur Hospital, BL Tower 1, Tonk Rd, Mahaveer Nagar, Jaipur, Rajasthan 302018',
    whatsappNumber: '919876543210',
    facebookUrl: 'https://facebook.com/finonest',
    instagramUrl: 'https://instagram.com/finonest',
    linkedinUrl: 'https://linkedin.com/company/finonest',
    twitterUrl: 'https://twitter.com/finonest',
    youtubeUrl: 'https://youtube.com/@finonest',
    metaTitle: 'Finonest - Your Trusted Loan Partner | Best Loan Rates in India',
    metaDescription: 'Get the best loan rates with 100% paperless processing. Personal loans, home loans, business loans with instant approval. Apply now at Finonest.',
    googleAnalyticsId: 'G-XXXXXXXXXX',
    maintenanceMode: false
  };

  await SiteSettings.updateOne({}, siteSettingsData, { upsert: true });
  
  console.log('Site settings seeded successfully');
  await mongoose.disconnect();
}

seedSiteSettings().catch(err => {
  console.error('Error seeding site settings:', err);
  process.exit(1);
});