import mongoose from 'mongoose';
import bcrypt from 'bcrypt';

// Connect to MongoDB
const MONGO_URI = process.env.MONGO_URI || 'mongodb://Fino:Finonest%40yogi%40super@m4w0kcwos8kwgkw0wg8sgk4w:27017/?directConnection=true';

async function seedRealData() {
  try {
    await mongoose.connect(MONGO_URI);
    console.log('✅ Connected to MongoDB');

    // Clear existing data
    const collections = ['stats', 'services', 'testimonials', 'partners', 'faqs', 'users', 'processsteps', 'whyusfeatures'];
    for (const collection of collections) {
      await mongoose.connection.db.collection(collection).deleteMany({});
    }

    // 1. Stats
    await mongoose.connection.db.collection('stats').insertMany([
      { title: 'Happy Customers', value: '5.8 Lacs+', icon: 'users', order: 1, published: true, createdAt: new Date() },
      { title: 'Cities Covered', value: '150+', icon: 'map-pin', order: 2, published: true, createdAt: new Date() },
      { title: 'Partner Banks', value: '50+', icon: 'building', order: 3, published: true, createdAt: new Date() },
      { title: 'Loans Disbursed', value: '₹61,000 Cr+', icon: 'trending-up', order: 4, published: true, createdAt: new Date() }
    ]);

    // 2. Services
    await mongoose.connection.db.collection('services').insertMany([
      {
        name: 'Home Loan',
        slug: 'home-loan',
        description: 'Get your dream home with our competitive home loan rates starting from 8.5% per annum.',
        shortDescription: 'Competitive rates from 8.5% PA',
        interestRate: '8.5% - 12%',
        processingFee: '0.5% - 1%',
        maxAmount: '₹5 Crores',
        tenure: 'Up to 30 years',
        features: ['Quick approval in 24 hours', 'Minimal documentation', 'Flexible repayment options', 'No prepayment charges'],
        eligibility: ['Age: 21-65 years', 'Minimum income: ₹25,000/month', 'CIBIL score: 650+', 'Employment: 2+ years'],
        documents: ['Income proof', 'Identity proof', 'Address proof', 'Property documents', 'Bank statements'],
        published: true,
        featured: true,
        order: 1,
        createdAt: new Date()
      },
      {
        name: 'Personal Loan',
        slug: 'personal-loan',
        description: 'Instant personal loans for all your needs with minimal documentation and quick approval.',
        shortDescription: 'Instant approval, minimal docs',
        interestRate: '10.5% - 24%',
        processingFee: '1% - 3%',
        maxAmount: '₹40 Lakhs',
        tenure: 'Up to 7 years',
        features: ['Instant approval', 'No collateral required', 'Flexible EMI options', 'Online application'],
        eligibility: ['Age: 21-60 years', 'Minimum income: ₹20,000/month', 'CIBIL score: 700+', 'Employment: 1+ years'],
        documents: ['Salary slips', 'Bank statements', 'Identity proof', 'Address proof'],
        published: true,
        featured: true,
        order: 2,
        createdAt: new Date()
      },
      {
        name: 'Car Loan',
        slug: 'car-loan',
        description: 'Drive your dream car today with our attractive car loan offers and easy EMI options.',
        shortDescription: 'Drive your dream car today',
        interestRate: '7.5% - 14%',
        processingFee: '0.5% - 2%',
        maxAmount: '₹1.5 Crores',
        tenure: 'Up to 7 years',
        features: ['Up to 90% financing', 'Quick processing', 'Competitive rates', 'Insurance assistance'],
        eligibility: ['Age: 21-65 years', 'Minimum income: ₹20,000/month', 'CIBIL score: 650+', 'Valid driving license'],
        documents: ['Income proof', 'Identity proof', 'Address proof', 'Car quotation', 'Insurance documents'],
        published: true,
        featured: true,
        order: 3,
        createdAt: new Date()
      },
      {
        name: 'Business Loan',
        slug: 'business-loan',
        description: 'Grow your business with our flexible business loan solutions tailored for entrepreneurs.',
        shortDescription: 'Fuel your business growth',
        interestRate: '11% - 18%',
        processingFee: '1% - 2%',
        maxAmount: '₹10 Crores',
        tenure: 'Up to 10 years',
        features: ['Collateral-free options', 'Quick disbursement', 'Flexible repayment', 'Business advisory support'],
        eligibility: ['Business vintage: 2+ years', 'Annual turnover: ₹10 Lakhs+', 'CIBIL score: 650+', 'ITR filing: 2 years'],
        documents: ['Business registration', 'Financial statements', 'ITR', 'Bank statements', 'GST returns'],
        published: true,
        featured: true,
        order: 4,
        createdAt: new Date()
      },
      {
        name: 'Credit Cards',
        slug: 'credit-cards',
        description: 'Choose from a wide range of credit cards with exciting rewards and cashback offers.',
        shortDescription: 'Rewards & cashback offers',
        interestRate: '3.5% per month',
        processingFee: 'Nil to ₹5,000',
        maxAmount: '₹25 Lakhs',
        tenure: 'Revolving credit',
        features: ['Reward points', 'Cashback offers', 'Airport lounge access', 'EMI conversion'],
        eligibility: ['Age: 21-60 years', 'Minimum income: ₹15,000/month', 'CIBIL score: 700+', 'Employment: 1+ years'],
        documents: ['Salary slips', 'Bank statements', 'Identity proof', 'Address proof'],
        published: true,
        featured: false,
        order: 5,
        createdAt: new Date()
      }
    ]);

    // 3. Testimonials
    await mongoose.connection.db.collection('testimonials').insertMany([
      {
        name: 'Rajesh Kumar',
        designation: 'Software Engineer',
        company: 'TCS',
        rating: 5,
        review: 'Finonest made my home loan process incredibly smooth. Got approval in just 2 days with competitive rates. Highly recommended!',
        image: '/assets/testimonials/rajesh.jpg',
        published: true,
        featured: true,
        createdAt: new Date()
      },
      {
        name: 'Priya Sharma',
        designation: 'Marketing Manager',
        company: 'Wipro',
        rating: 5,
        review: 'Excellent service for personal loan. The team was very professional and guided me through every step. Quick disbursement!',
        image: '/assets/testimonials/priya.jpg',
        published: true,
        featured: true,
        createdAt: new Date()
      },
      {
        name: 'Amit Patel',
        designation: 'Business Owner',
        company: 'Patel Enterprises',
        rating: 4,
        review: 'Got my business loan approved without any hassle. The interest rates are competitive and the process is transparent.',
        image: '/assets/testimonials/amit.jpg',
        published: true,
        featured: true,
        createdAt: new Date()
      },
      {
        name: 'Sneha Reddy',
        designation: 'Doctor',
        company: 'Apollo Hospitals',
        rating: 5,
        review: 'Amazing experience with car loan. The documentation was minimal and the approval was instant. Thank you Finonest!',
        image: '/assets/testimonials/sneha.jpg',
        published: true,
        featured: true,
        createdAt: new Date()
      }
    ]);

    // 4. Banking Partners
    await mongoose.connection.db.collection('partners').insertMany([
      { name: 'HDFC Bank', slug: 'hdfc-bank', logo: '/assets/partners/hdfc.png', type: 'bank', published: true, featured: true, order: 1 },
      { name: 'ICICI Bank', slug: 'icici-bank', logo: '/assets/partners/icici.png', type: 'bank', published: true, featured: true, order: 2 },
      { name: 'Axis Bank', slug: 'axis-bank', logo: '/assets/partners/axis.png', type: 'bank', published: true, featured: true, order: 3 },
      { name: 'State Bank of India', slug: 'sbi', logo: '/assets/partners/sbi.png', type: 'bank', published: true, featured: true, order: 4 },
      { name: 'Kotak Mahindra Bank', slug: 'kotak-bank', logo: '/assets/partners/kotak.png', type: 'bank', published: true, featured: true, order: 5 },
      { name: 'Punjab National Bank', slug: 'pnb', logo: '/assets/partners/pnb.png', type: 'bank', published: true, featured: true, order: 6 }
    ]);

    // 5. FAQs
    await mongoose.connection.db.collection('faqs').insertMany([
      {
        question: 'What is the minimum CIBIL score required for a loan?',
        answer: 'The minimum CIBIL score varies by loan type. For personal loans, we require 700+, while for home and car loans, 650+ is acceptable.',
        category: 'eligibility',
        published: true,
        order: 1,
        createdAt: new Date()
      },
      {
        question: 'How long does the loan approval process take?',
        answer: 'Our loan approval process is designed to be quick. Personal loans can be approved in 24 hours, while home loans typically take 3-7 working days.',
        category: 'process',
        published: true,
        order: 2,
        createdAt: new Date()
      },
      {
        question: 'What documents are required for loan application?',
        answer: 'Basic documents include identity proof, address proof, income proof, and bank statements. Specific requirements vary by loan type.',
        category: 'documents',
        published: true,
        order: 3,
        createdAt: new Date()
      },
      {
        question: 'Can I prepay my loan without charges?',
        answer: 'Yes, most of our loan products allow prepayment without charges. However, terms may vary by lender and loan type.',
        category: 'repayment',
        published: true,
        order: 4,
        createdAt: new Date()
      },
      {
        question: 'Is there any processing fee for loans?',
        answer: 'Processing fees vary by loan type and amount. We offer competitive processing fees starting from 0.5% of the loan amount.',
        category: 'fees',
        published: true,
        order: 5,
        createdAt: new Date()
      }
    ]);

    // 6. Process Steps
    await mongoose.connection.db.collection('processsteps').insertMany([
      {
        title: 'Apply Online',
        description: 'Fill out our simple online application form with basic details',
        icon: 'file-text',
        order: 1,
        published: true,
        createdAt: new Date()
      },
      {
        title: 'Document Verification',
        description: 'Upload required documents for quick verification',
        icon: 'check-circle',
        order: 2,
        published: true,
        createdAt: new Date()
      },
      {
        title: 'Quick Approval',
        description: 'Get instant approval with competitive interest rates',
        icon: 'thumbs-up',
        order: 3,
        published: true,
        createdAt: new Date()
      },
      {
        title: 'Loan Disbursement',
        description: 'Receive funds directly in your bank account',
        icon: 'credit-card',
        order: 4,
        published: true,
        createdAt: new Date()
      }
    ]);

    // 7. Why Choose Us Features
    await mongoose.connection.db.collection('whyusfeatures').insertMany([
      {
        title: 'Quick Approval',
        description: 'Get loan approval in as fast as 24 hours with minimal documentation',
        icon: 'zap',
        order: 1,
        published: true,
        createdAt: new Date()
      },
      {
        title: 'Best Interest Rates',
        description: 'Competitive interest rates starting from 8.5% per annum',
        icon: 'percent',
        order: 2,
        published: true,
        createdAt: new Date()
      },
      {
        title: '50+ Bank Partners',
        description: 'Wide network of banking partners to get you the best deals',
        icon: 'users',
        order: 3,
        published: true,
        createdAt: new Date()
      },
      {
        title: '24/7 Support',
        description: 'Round-the-clock customer support for all your queries',
        icon: 'headphones',
        order: 4,
        published: true,
        createdAt: new Date()
      },
      {
        title: 'Simple Process',
        description: 'Hassle-free application process with minimal paperwork',
        icon: 'file-text',
        order: 5,
        published: true,
        createdAt: new Date()
      },
      {
        title: '100% Transparent',
        description: 'No hidden charges, complete transparency in all dealings',
        icon: 'eye',
        order: 6,
        published: true,
        createdAt: new Date()
      }
    ]);

    // 8. Admin User
    const hashedPassword = await bcrypt.hash('Admin@123', 10);
    await mongoose.connection.db.collection('users').insertOne({
      email: 'admin@finonest.com',
      password: hashedPassword,
      name: 'Finonest Admin',
      role: 'superadmin',
      isActive: true,
      createdAt: new Date(),
      updatedAt: new Date()
    });

    console.log('✅ Real data seeded successfully!');
    console.log('📊 Seeded:');
    console.log('  - 4 Stats');
    console.log('  - 5 Services');
    console.log('  - 4 Testimonials');
    console.log('  - 6 Banking Partners');
    console.log('  - 5 FAQs');
    console.log('  - 4 Process Steps');
    console.log('  - 6 Why Choose Us Features');
    console.log('  - 1 Admin User (admin@finonest.com / Admin@123)');

  } catch (error) {
    console.error('❌ Error seeding data:', error);
  } finally {
    await mongoose.disconnect();
    console.log('🔌 Disconnected from MongoDB');
  }
}

seedRealData();