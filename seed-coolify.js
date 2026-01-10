const mongoose = require('mongoose');
const bcrypt = require('bcrypt');

// MongoDB connection
const MONGO_URI = process.env.MONGO_URI || 'mongodb://Fino:Finonest%40yogi%40super@m4w0kcwos8kwgkw0wg8sgk4w:27017/?directConnection=true';

// Simple schemas
const userSchema = new mongoose.Schema({
  email: String,
  name: String,
  passwordHash: String,
  role: String,
  isActive: Boolean
});

const statSchema = new mongoose.Schema({
  label: String,
  value: String,
  suffix: String,
  icon: String,
  order: Number,
  published: Boolean,
  publishedAt: Date
});

const serviceSchema = new mongoose.Schema({
  slug: String,
  title: String,
  shortDescription: String,
  description: String,
  rate: String,
  maxAmount: String,
  maxTenure: String,
  highlight: Boolean,
  published: Boolean,
  publishedAt: Date,
  features: [String],
  eligibility: [String],
  documents: [String]
});

const User = mongoose.model('User', userSchema);
const Stat = mongoose.model('Stat', statSchema);
const Service = mongoose.model('Service', serviceSchema);

async function seedData() {
  try {
    await mongoose.connect(MONGO_URI);
    console.log('Connected to MongoDB');

    // Create admin user
    const adminPassword = await bcrypt.hash('Admin@123', 10);
    await User.updateOne(
      { email: 'admin@finonest.com' },
      {
        email: 'admin@finonest.com',
        name: 'Super Admin',
        passwordHash: adminPassword,
        role: 'superadmin',
        isActive: true
      },
      { upsert: true }
    );

    // Seed stats
    const stats = [
      { label: 'Customers Annually', value: '5.8 Lacs', suffix: '+', icon: 'users', order: 1, published: true, publishedAt: new Date() },
      { label: 'Cities Covered', value: '150', suffix: '+', icon: 'map', order: 2, published: true, publishedAt: new Date() },
      { label: 'Branches', value: '587', suffix: '+', icon: 'building', order: 3, published: true, publishedAt: new Date() },
      { label: 'Disbursed Annually', value: '61,000', suffix: 'Cr+', icon: 'rupee', order: 4, published: true, publishedAt: new Date() }
    ];
    
    for (const stat of stats) {
      await Stat.updateOne({ label: stat.label }, stat, { upsert: true });
    }

    // Seed services
    const services = [
      {
        slug: 'home-loan',
        title: 'Home Loan',
        shortDescription: 'Instant approval at lowest interest rates',
        description: 'Get the keys to your dream home with our affordable home loans.',
        rate: '8.35% p.a.',
        maxAmount: '₹5 Cr',
        maxTenure: '30 years',
        highlight: true,
        published: true,
        publishedAt: new Date(),
        features: ['24 Hour Approval', '100% Transparent', '50+ Bank Partners'],
        eligibility: ['Indian Resident or NRI', 'Age: 21-65 years', 'Minimum income: ₹25,000/month'],
        documents: ['Identity Proof', 'Address Proof', 'Income Proof', 'Property Documents']
      },
      {
        slug: 'personal-loan',
        title: 'Personal Loan',
        shortDescription: 'Paperless process at low rate',
        description: 'Get instant personal loans up to ₹40 lakhs with minimal documentation.',
        rate: '10.49% p.a.',
        maxAmount: '₹40 L',
        maxTenure: '5 years',
        highlight: false,
        published: true,
        publishedAt: new Date(),
        features: ['Instant Disbursal', 'Minimal Docs', 'Paperless Process'],
        eligibility: ['Indian Resident', 'Age: 21-60 years', 'Minimum income: ₹25,000/month'],
        documents: ['PAN Card & Aadhaar', 'Salary slips', 'Bank statement']
      }
    ];

    for (const service of services) {
      await Service.updateOne({ slug: service.slug }, service, { upsert: true });
    }

    console.log('✅ Data seeded successfully!');
    process.exit(0);
  } catch (error) {
    console.error('❌ Seeding failed:', error);
    process.exit(1);
  }
}

seedData();