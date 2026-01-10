import mongoose from 'mongoose';
import bcrypt from 'bcrypt';

const MONGO_URI = process.env.MONGO_URI || 'mongodb://localhost:27017/finonest-dev';

export async function autoSeedData() {
  try {
    // Check if data already exists
    const statsCount = await mongoose.connection.db.collection('stats').countDocuments();
    if (statsCount > 0) {
      console.log('📊 Data already exists, skipping seed');
      return;
    }

    console.log('🌱 Seeding comprehensive data...');

    // 1. Stats
    await mongoose.connection.db.collection('stats').insertMany([
      { title: 'Happy Customers', value: '5.8 Lacs+', icon: 'users', order: 1, published: true, createdAt: new Date() },
      { title: 'Cities Covered', value: '150+', icon: 'map-pin', order: 2, published: true, createdAt: new Date() },
      { title: 'Partner Banks', value: '50+', icon: 'building', order: 3, published: true, createdAt: new Date() },
      { title: 'Loans Disbursed', value: '₹61,000 Cr+', icon: 'trending-up', order: 4, published: true, createdAt: new Date() }
    ]);

    // 2. Services (with proper structure for HeroSection)
    await mongoose.connection.db.collection('services').insertMany([
      {
        slug: 'home-loan',
        title: 'Home Loan',
        shortDescription: 'Instant approval at lowest interest rates',
        description: 'Get your dream home with our competitive home loan rates starting from 8.5% per annum.',
        interestRate: '8.5% - 12%',
        processingFee: '0.5% - 1%',
        maxAmount: '₹5 Crores',
        tenure: 'Up to 30 years',
        rate: '8.5%',
        highlight: true,
        features: ['Quick approval in 24 hours', 'Minimal documentation', 'Flexible repayment options', 'No prepayment charges'],
        eligibility: ['Age: 21-65 years', 'Minimum income: ₹25,000/month', 'CIBIL score: 650+', 'Employment: 2+ years'],
        documents: ['Income proof', 'Identity proof', 'Address proof', 'Property documents', 'Bank statements'],
        published: true,
        status: 'published',
        featured: true,
        order: 1,
        createdAt: new Date(),
        publishedAt: new Date()
      },
      {
        slug: 'personal-loan',
        title: 'Personal Loan',
        shortDescription: 'Paperless process at low rate',
        description: 'Instant personal loans for all your needs with minimal documentation and quick approval.',
        interestRate: '10.5% - 24%',
        processingFee: '1% - 3%',
        maxAmount: '₹40 Lakhs',
        tenure: 'Up to 7 years',
        rate: '10.5%',
        highlight: true,
        features: ['Instant approval', 'No collateral required', 'Flexible EMI options', 'Online application'],
        eligibility: ['Age: 21-60 years', 'Minimum income: ₹20,000/month', 'CIBIL score: 700+', 'Employment: 1+ years'],
        documents: ['Salary slips', 'Bank statements', 'Identity proof', 'Address proof'],
        published: true,
        status: 'published',
        featured: true,
        order: 2,
        createdAt: new Date(),
        publishedAt: new Date()
      },
      {
        slug: 'car-loan',
        title: 'New Car Loan',
        shortDescription: 'Drive away your dream car today.',
        description: 'Drive your dream car today with our attractive car loan offers and easy EMI options.',
        interestRate: '7.5% - 14%',
        processingFee: '0.5% - 2%',
        maxAmount: '₹1.5 Crores',
        tenure: 'Up to 7 years',
        rate: '9%',
        highlight: true,
        features: ['Up to 90% financing', 'Quick processing', 'Competitive rates', 'Insurance assistance'],
        eligibility: ['Age: 21-65 years', 'Minimum income: ₹20,000/month', 'CIBIL score: 650+', 'Valid driving license'],
        documents: ['Income proof', 'Identity proof', 'Address proof', 'Car quotation', 'Insurance documents'],
        published: true,
        status: 'published',
        featured: true,
        order: 3,
        createdAt: new Date(),
        publishedAt: new Date()
      },
      {
        slug: 'credit-cards',
        title: 'Credit Card',
        shortDescription: 'Choose cards from all top banks',
        description: 'Choose from a wide range of credit cards with exciting rewards and cashback offers.',
        interestRate: '3.5% per month',
        processingFee: 'Nil to ₹5,000',
        maxAmount: '₹25 Lakhs',
        tenure: 'Revolving credit',
        rate: 'N/A',
        highlight: true,
        features: ['Reward points', 'Cashback offers', 'Airport lounge access', 'EMI conversion'],
        eligibility: ['Age: 21-60 years', 'Minimum income: ₹15,000/month', 'CIBIL score: 700+', 'Employment: 1+ years'],
        documents: ['Salary slips', 'Bank statements', 'Identity proof', 'Address proof'],
        published: true,
        status: 'published',
        featured: false,
        order: 5,
        createdAt: new Date(),
        publishedAt: new Date()
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

    // 8. Navigation Items
    const navItems = await mongoose.connection.db.collection('navitems').insertMany([
      { label: 'Home', href: '/', position: 'header', order: 1, published: true, target: '_self', createdAt: new Date() },
      { label: 'Services', href: '/services', position: 'header', order: 2, published: true, target: '_self', createdAt: new Date() },
      { label: 'Credit Score', href: '/credit-score', position: 'header', order: 3, published: true, target: '_self', createdAt: new Date() },
      { label: 'EMI Calculator', href: '/emi-calculator', position: 'header', order: 4, published: true, target: '_self', createdAt: new Date() },
      { label: 'Blog', href: '/blog', position: 'header', order: 5, published: true, target: '_self', createdAt: new Date() },
      { label: 'About', href: '/about', position: 'header', order: 6, published: true, target: '_self', createdAt: new Date() },
      { label: 'Contact', href: '/contact', position: 'header', order: 7, published: true, target: '_self', createdAt: new Date() }
    ]);

    // Get the Services parent ID for dropdown items
    const servicesParent = navItems.insertedIds[1]; // Services is at index 1
    
    // Add Services dropdown items
    await mongoose.connection.db.collection('navitems').insertMany([
      { label: 'Home Loan', href: '/services/home-loan', position: 'header', order: 1, parentNavId: servicesParent, published: true, target: '_self', createdAt: new Date() },
      { label: 'Used Car Loan', href: '/services/used-car-loan', position: 'header', order: 2, parentNavId: servicesParent, published: true, target: '_self', createdAt: new Date() },
      { label: 'Personal Loan', href: '/services/personal-loan', position: 'header', order: 3, parentNavId: servicesParent, published: true, target: '_self', createdAt: new Date() },
      { label: 'Business Loan', href: '/services/business-loan', position: 'header', order: 4, parentNavId: servicesParent, published: true, target: '_self', createdAt: new Date() },
      { label: 'Loan Against Property', href: '/services/loan-against-property', position: 'header', order: 5, parentNavId: servicesParent, published: true, target: '_self', createdAt: new Date() },
      { label: 'Credit Cards', href: '/services/credit-cards', position: 'header', order: 6, parentNavId: servicesParent, published: true, target: '_self', createdAt: new Date() },
      { label: 'Finobizz Learning', href: '/services/finobizz-learning', position: 'header', order: 7, parentNavId: servicesParent, published: true, target: '_self', createdAt: new Date() }
    ]);



    // 9. Promotional Banners
    await mongoose.connection.db.collection('banners').insertMany([
      {
        title: 'Quick Sanction - Home Loan - Instant approval at lowest interest rates',
        link: '/services/home-loan',
        priority: 4,
        active: true,
        createdAt: new Date()
      },
      {
        title: 'Quick Disbursal - Personal Loan - Paperless process at low rate',
        link: '/services/personal-loan',
        priority: 3,
        active: true,
        createdAt: new Date()
      },
      {
        title: 'Lowest EMI Ride - New Car Loan - Drive away your dream car today',
        link: '/services/car-loan',
        priority: 2,
        active: true,
        createdAt: new Date()
      },
      {
        title: 'Rewards Unlimited - Credit Card - Choose cards from all top banks',
        link: '/services/credit-cards',
        priority: 1,
        active: true,
        createdAt: new Date()
      }
    ]);

    // 10. Site Settings
    await mongoose.connection.db.collection('sitesettings').insertOne({
      siteName: 'Finonest',
      tagline: 'Your Trusted Financial Partner',
      description: 'Get the best loan deals with competitive interest rates and quick approval process.',
      contactEmail: 'info@finonest.com',
      contactPhone: '+91-9876543210',
      address: 'Mumbai, Maharashtra, India',
      socialMedia: {
        facebook: 'https://facebook.com/finonest',
        twitter: 'https://twitter.com/finonest',
        linkedin: 'https://linkedin.com/company/finonest',
        instagram: 'https://instagram.com/finonest'
      },
      seoTitle: 'Finonest - Best Loan Deals & Financial Services',
      seoDescription: 'Get instant loans with competitive rates. Home loans, personal loans, car loans, and business loans with quick approval.',
      seoKeywords: 'loans, home loan, personal loan, car loan, business loan, credit cards, financial services',
      published: true,
      createdAt: new Date()
    });

    // 11. Admin User
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

    console.log('✅ Comprehensive auto-seed completed successfully');
    console.log('📊 Seeded: Stats, Services, Testimonials, Partners, FAQs, Process Steps, Why Us Features, Navigation, Site Settings, Admin User');
  } catch (error) {
    console.error('❌ Auto-seed error:', error.message);
  }
}