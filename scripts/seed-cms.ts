import mongoose from 'mongoose';
import bcrypt from 'bcrypt';
import * as Models from '../src/models';

const MONGO_URI = process.env.MONGO_URI || 'mongodb://localhost:27017/finonest-dev';

async function seedCMS() {
  console.log('🌱 Starting CMS Content Seeding...\n');
  
  try {
    await mongoose.connect(MONGO_URI);
    console.log('✅ Connected to MongoDB\n');

    // 1. Create SuperAdmin User
    console.log('📝 Creating SuperAdmin user...');
    const adminEmail = process.env.SEED_ADMIN_EMAIL || 'admin@finonest.com';
    const adminPassword = process.env.SEED_ADMIN_PASSWORD || 'Admin@123';
    const existingAdmin = await Models.User.findOne({ email: adminEmail });
    if (!existingAdmin) {
      const passwordHash = await bcrypt.hash(adminPassword, 10);
      await Models.User.create({
        email: adminEmail,
        name: 'Super Admin',
        passwordHash,
        role: 'superadmin',
        isActive: true
      });
      console.log(`   ✅ Created SuperAdmin: ${adminEmail} / ${adminPassword}`);
    } else {
      console.log(`   ℹ️  SuperAdmin already exists: ${adminEmail}`);
    }

    // 2. Seed Stats
    console.log('\n📊 Seeding Stats...');
    const stats = [
      { label: 'Customers Annually', value: '5.8 Lacs', suffix: '+', icon: 'users', order: 1, published: true },
      { label: 'Cities Covered', value: '150', suffix: '+', icon: 'map', order: 2, published: true },
      { label: 'Branches', value: '587', suffix: '+', icon: 'building', order: 3, published: true },
      { label: 'Disbursed Annually', value: '61,000', suffix: 'Cr+', icon: 'rupee', order: 4, published: true }
    ];
    for (const stat of stats) {
      await Models.Stat.updateOne(
        { label: stat.label },
        { ...stat, publishedAt: new Date() },
        { upsert: true }
      );
    }
    console.log(`   ✅ Seeded ${stats.length} stats`);

    // 3. Seed Process Steps
    console.log('\n🔄 Seeding Process Steps...');
    const processSteps = [
      {
        title: 'Apply Online',
        description: 'Fill out our simple online application form in just 5 minutes with basic details',
        icon: 'file',
        order: 1,
        published: true,
        publishedAt: new Date()
      },
      {
        title: 'Document Verification',
        description: 'Upload documents digitally or opt for doorstep pickup by our executive',
        icon: 'search',
        order: 2,
        published: true,
        publishedAt: new Date()
      },
      {
        title: 'Quick Approval',
        description: 'Get approval within 24 hours after document verification is complete',
        icon: 'check',
        order: 3,
        published: true,
        publishedAt: new Date()
      },
      {
        title: 'Loan Disbursement',
        description: 'Amount credited directly to your bank account within 48 hours of approval',
        icon: 'wallet',
        order: 4,
        published: true,
        publishedAt: new Date()
      }
    ];
    for (const step of processSteps) {
      await Models.ProcessStep.updateOne(
        { title: step.title },
        step,
        { upsert: true }
      );
    }
    console.log(`   ✅ Seeded ${processSteps.length} process steps`);

    // 4. Seed FAQs
    console.log('\n❓ Seeding FAQs...');
    const faqs = [
      {
        question: 'What are the basic eligibility criteria for a home loan?',
        answer: 'To be eligible for a home loan, you must be at least 21 years old, have a minimum monthly income of ₹25,000, and have a CIBIL score of 700 or above. You should also have a stable employment history of at least 2 years.',
        category: 'eligibility',
        order: 1,
        published: true,
        publishedAt: new Date()
      },
      {
        question: 'What documents are required for a personal loan?',
        answer: 'For a personal loan, you need to provide identity proof (Aadhaar/PAN), address proof, income proof (salary slips or bank statements), and employment certificate. The exact documents may vary based on your employment type.',
        category: 'documents',
        order: 1,
        published: true,
        publishedAt: new Date()
      },
      {
        question: 'How long does it take to get loan approval?',
        answer: 'Most loan applications are approved within 24-48 hours after document verification. The entire process from application to disbursement typically takes 3-5 business days.',
        category: 'process',
        order: 1,
        published: true,
        publishedAt: new Date()
      },
      {
        question: 'What is the minimum CIBIL score required?',
        answer: 'The minimum CIBIL score requirement varies by loan type. For personal loans, a score of 650+ is preferred, while home loans typically require 700+. However, we work with multiple banks and can help you find the best option based on your profile.',
        category: 'eligibility',
        order: 2,
        published: true,
        publishedAt: new Date()
      },
      {
        question: 'Can I prepay my loan?',
        answer: 'Yes, most loans allow prepayment. Some banks may charge a small prepayment fee, while others allow free prepayment after a certain period. Our team can help you understand the prepayment terms for your specific loan.',
        category: 'repayment',
        order: 1,
        published: true,
        publishedAt: new Date()
      }
    ];
    for (const faq of faqs) {
      await Models.FAQ.updateOne(
        { question: faq.question },
        faq,
        { upsert: true }
      );
    }
    console.log(`   ✅ Seeded ${faqs.length} FAQs`);

    // 5. Seed Navigation Items
    console.log('\n🧭 Seeding Navigation Items...');
    
    // First create parent nav items
    const parentNavItems = [
      { label: 'Home', href: '/', position: 'header', order: 1, published: true },
      { label: 'About Us', href: '/about', position: 'header', order: 2, published: true },
      { label: 'Services', href: '/services', position: 'header', order: 3, published: true },
      { label: 'Blog', href: '/blog', position: 'header', order: 4, published: true },
      { label: 'Contact', href: '/contact', position: 'header', order: 5, published: true },
      { label: 'Apply Now', href: '/apply', position: 'header', order: 6, published: true, highlight: true },
      { label: 'Home', href: '/', position: 'footer', order: 1, published: true },
      { label: 'About Us', href: '/about', position: 'footer', order: 2, published: true },
      { label: 'Services', href: '/services', position: 'footer', order: 3, published: true },
      { label: 'Apply Now', href: '/apply', position: 'footer', order: 4, published: true },
      { label: 'Banking Partners', href: '/banking-partners', position: 'footer', order: 5, published: true },
      { label: 'Contact', href: '/contact', position: 'footer', order: 6, published: true }
    ];
    
    for (const item of parentNavItems) {
      await Models.NavItem.updateOne(
        { label: item.label, position: item.position },
        { ...item, publishedAt: new Date() },
        { upsert: true }
      );
    }
    
    // Then create service sub-menu items
    const servicesParent = await Models.NavItem.findOne({ label: 'Services', position: 'header' });
    if (servicesParent) {
      const serviceNavItems = [
        { label: 'Home Loan', href: '/services/home-loan', position: 'header', order: 1, parentNavId: servicesParent._id, published: true },
        { label: 'Personal Loan', href: '/services/personal-loan', position: 'header', order: 2, parentNavId: servicesParent._id, published: true },
        { label: 'Car Loan', href: '/services/car-loan', position: 'header', order: 3, parentNavId: servicesParent._id, published: true },
        { label: 'Used Car Loan', href: '/services/used-car-loan', position: 'header', order: 4, parentNavId: servicesParent._id, published: true },
        { label: 'Business Loan', href: '/services/business-loan', position: 'header', order: 5, parentNavId: servicesParent._id, published: true },
        { label: 'Credit Cards', href: '/services/credit-cards', position: 'header', order: 6, parentNavId: servicesParent._id, published: true },
        { label: 'Loan Against Property', href: '/services/loan-against-property', position: 'header', order: 7, parentNavId: servicesParent._id, published: true },
        { label: 'Finobizz Learning', href: '/services/finobizz-learning', position: 'header', order: 8, parentNavId: servicesParent._id, published: true }
      ];
      
      for (const item of serviceNavItems) {
        await Models.NavItem.updateOne(
          { label: item.label, parentNavId: item.parentNavId },
          { ...item, publishedAt: new Date() },
          { upsert: true }
        );
      }
    }
    
    console.log(`   ✅ Seeded navigation items with dropdown`);

    // 6. Seed Services
    console.log('\n🏦 Seeding Services...');
    const services = [
      {
        slug: 'home-loan',
        title: 'Home Loan',
        shortDescription: 'Instant approval at lowest interest rates',
        description: 'Get the keys to your dream home with our affordable home loans. Enjoy competitive interest rates starting at just 8.35% p.a., flexible tenure up to 30 years, and hassle-free approval process.',
        rate: '8.35% p.a.',
        maxAmount: '₹5 Cr',
        maxTenure: '30 years',
        highlight: true,
        published: true,
        publishedAt: new Date(),
        features: ['24 Hour Approval', '100% Transparent', '50+ Bank Partners'],
        eligibility: ['Indian Resident or NRI', 'Age: 21-65 years', 'Minimum income: ₹25,000/month', 'Good credit score (650+)'],
        documents: ['Identity Proof', 'Address Proof', 'Income Proof', 'Property Documents']
      },
      {
        slug: 'personal-loan',
        title: 'Personal Loan',
        shortDescription: 'Paperless process at low rate',
        description: 'Whether it\'s a dream wedding, home renovation, or medical emergency – get instant personal loans up to ₹40 lakhs with minimal documentation and 24-hour disbursal.',
        rate: '10.49% p.a.',
        maxAmount: '₹40 L',
        maxTenure: '5 years',
        highlight: false,
        published: true,
        publishedAt: new Date(),
        features: ['Instant Disbursal', 'Minimal Docs', 'Paperless Process'],
        eligibility: ['Indian Resident', 'Age: 21-60 years', 'Minimum income: ₹25,000/month', 'Credit score: 700+ preferred'],
        documents: ['PAN Card & Aadhaar', 'Salary slips', 'Bank statement', 'Employment ID']
      },
      {
        slug: 'car-loan',
        title: 'New Car Loan',
        shortDescription: 'Drive away your dream car today.',
        description: 'Get behind the wheel of your dream car with our easy car loans. Enjoy competitive rates starting at 7.99% p.a., 100% on-road financing, and same-day approval.',
        rate: '7.99% p.a.',
        maxAmount: '₹1 Cr',
        maxTenure: '7 years',
        highlight: false,
        published: true,
        publishedAt: new Date(),
        features: ['Same Day Approval', '100% Financing', 'Flexible Repayment'],
        eligibility: ['Indian Resident', 'Age: 21-65 years', 'Minimum income: ₹20,000/month', 'Credit score: 650+ preferred'],
        documents: ['Identity Proof', 'Income Proof', 'Bank Statements', 'Vehicle quotation']
      },
      {
        slug: 'credit-cards',
        title: 'Credit Card',
        shortDescription: 'Choose cards from all top banks',
        description: 'Find the perfect credit card that matches your lifestyle. Compare rewards, travel, and cashback cards from India\'s top banks and get instant approval.',
        rate: 'Varies',
        maxAmount: '₹10 L',
        maxTenure: 'Lifetime',
        highlight: false,
        published: true,
        publishedAt: new Date(),
        features: ['Best Offers', 'Expert Advice', 'Instant Approval'],
        eligibility: ['Indian Resident', 'Age: 21-65 years', 'Minimum income: ₹25,000/month', 'Good credit score (700+)'],
        documents: ['PAN Card', 'Aadhaar Card', 'Salary slip', 'Bank statement']
      }
    ];
    for (const service of services) {
      await Models.Service.updateOne(
        { slug: service.slug },
        service,
        { upsert: true }
      );
    }
    console.log(`   ✅ Seeded ${services.length} services`);

    // 6.1. Seed Service Cards for Hero Section
    console.log('\n🎯 Seeding Service Cards...');
    const serviceCards = [
      {
        _id: '1',
        slug: 'home-loan',
        title: 'Home Loan',
        shortDescription: 'Instant approval at lowest interest rates',
        highlight: true,
        ctaText: 'Apply Now',
        ctaLink: '/services/home-loan',
        published: true,
        publishedAt: new Date()
      },
      {
        _id: '2',
        slug: 'personal-loan',
        title: 'Personal Loan',
        shortDescription: 'Paperless process at low rate',
        highlight: false,
        ctaText: 'Apply Now',
        ctaLink: '/services/personal-loan',
        published: true,
        publishedAt: new Date()
      },
      {
        _id: '3',
        slug: 'car-loan',
        title: 'New Car Loan',
        shortDescription: 'Drive away your dream car today.',
        highlight: false,
        ctaText: 'Apply Now',
        ctaLink: '/services/car-loan',
        published: true,
        publishedAt: new Date()
      },
      {
        _id: '4',
        slug: 'credit-cards',
        title: 'Credit Card',
        shortDescription: 'Choose cards from all top banks',
        highlight: false,
        ctaText: 'Apply Now',
        ctaLink: '/services/credit-cards',
        published: true,
        publishedAt: new Date()
      }
    ];
    for (const card of serviceCards) {
      await Models.ServiceCard.updateOne(
        { slug: card.slug },
        card,
        { upsert: true }
      );
    }
    console.log(`   ✅ Seeded ${serviceCards.length} service cards`);

    // 7. Seed Footer
    console.log('\n🦶 Seeding Footer...');
    const footer = {
      companyName: 'Finonest',
      description: 'Finonest is your trusted partner for all financial needs. We simplify the loan process with transparency, speed, and personalized solutions.',
      quickLinks: [
        { label: 'Home', href: '/' },
        { label: 'About Us', href: '/about' },
        { label: 'Our Services', href: '/services' },
        { label: 'Apply Now', href: '/apply' },
        { label: 'Banking Partners', href: '/banking-partners' },
        { label: 'Contact', href: '/contact' }
      ],
      services: [
        { label: 'Home Loan', href: '/services/home-loan' },
        { label: 'Personal Loan', href: '/services/personal-loan' },
        { label: 'Business Loan', href: '/services/business-loan' },
        { label: 'Car Loan', href: '/services/car-loan' },
        { label: 'Credit Cards', href: '/services/credit-cards' }
      ],
      contactInfo: {
        address: '3rd Floor, Besides Jaipur Hospital, BL Tower 1, Tonk Rd, Mahaveer Nagar, Jaipur, Rajasthan 302018',
        phone: '+91 94625 53887',
        email: 'info@finonest.com',
        whatsapp: '919462553887',
        instagram: '@finonest.india'
      },
      socialLinks: [
        { platform: 'instagram', url: 'https://instagram.com/finonest.india' },
        { platform: 'google', url: 'https://g.page/r/finonest/review', label: 'Leave a Review on Google' }
      ],
      legalLinks: [
        { label: 'Privacy Policy', href: '/privacy-policy' },
        { label: 'Terms & Conditions', href: '/terms-and-conditions' }
      ],
      copyright: '© 2026 Finonest. All rights reserved.',
      published: true,
      publishedAt: new Date()
    };
    await Models.Footer.updateOne({}, footer, { upsert: true });
    console.log('   ✅ Seeded footer content');

    // 7. Seed WhyUs Features
    console.log('\n⭐ Seeding WhyUs Features...');
    const whyUsFeatures = [
      {
        title: 'Quick Approval',
        description: 'Get your loan approved within 24 hours with minimal documentation',
        icon: 'zap',
        order: 1,
        published: true,
        publishedAt: new Date()
      },
      {
        title: 'Simple Process',
        description: 'Easy online application with doorstep document collection',
        icon: 'file',
        order: 2,
        published: true,
        publishedAt: new Date()
      },
      {
        title: 'Best Rates',
        description: 'Competitive interest rates with flexible repayment options',
        icon: 'award',
        order: 3,
        published: true,
        publishedAt: new Date()
      },
      {
        title: '24/7 Support',
        description: 'Dedicated relationship managers for personalized assistance',
        icon: 'headphones',
        order: 4,
        published: true,
        publishedAt: new Date()
      },
      {
        title: '50+ Bank Partners',
        description: 'Wide network of banking partners for best loan offers',
        icon: 'users',
        order: 5,
        published: true,
        publishedAt: new Date()
      },
      {
        title: '100% Transparent',
        description: 'No hidden charges or processing fees surprises',
        icon: 'check',
        order: 6,
        published: true,
        publishedAt: new Date()
      }
    ];
    for (const feature of whyUsFeatures) {
      await Models.WhyUsFeature.updateOne(
        { title: feature.title },
        feature,
        { upsert: true }
      );
    }
    console.log(`   ✅ Seeded ${whyUsFeatures.length} WhyUs features`);

    // 8. Seed Services
    console.log('\n💼 Seeding Services...');
    const services = [
      {
        slug: 'home-loan',
        title: 'Home Loan',
        shortDescription: 'Instant approval at lowest interest rates',
        description: 'Get the best home loan rates with flexible repayment options. Up to ₹5 Crores financing available.',
        rate: '8.5%',
        highlight: true,
        eligibility: ['Age 21-65', 'Min Income ₹25,000/month', 'CIBIL 700+'],
        features: [
          { title: 'Quick Disbursal', description: 'Quick processing' },
          { title: 'Flexible Tenure', description: 'Up to 30 years' },
          { title: 'Low Processing Fee', description: '0.5% of loan amount' }
        ],
        status: 'published',
        publishedAt: new Date()
      },
      {
        slug: 'personal-loan',
        title: 'Personal Loan',
        shortDescription: 'Paperless process at low rate',
        description: 'Quick and easy personal loans with minimal documentation. Get funds up to ₹40 Lakhs.',
        rate: '10.5%',
        highlight: true,
        eligibility: ['Age 21-58', 'Min Income ₹15,000/month', 'CIBIL 650+'],
        features: [
          { title: 'Lowest EMI', description: 'Quick processing' },
          { title: 'No Collateral', description: 'Unsecured loan' },
          { title: 'Flexible Tenure', description: 'Up to 7 years' }
        ],
        status: 'published',
        publishedAt: new Date()
      },
      {
        slug: 'car-loan',
        title: 'New Car Loan',
        shortDescription: 'Drive away your dream car today.',
        description: 'Finance your dream car with competitive rates. New and used car loans available.',
        rate: '9%',
        highlight: true,
        eligibility: ['Age 21-65', 'Min Income ₹20,000/month', 'CIBIL 675+'],
        features: [
          { title: 'Lowest EMI Ride', description: 'Quick processing' },
          { title: 'Flexible Tenure', description: 'Up to 7 years' },
          { title: 'Low Processing Fee', description: '0.5% of loan amount' }
        ],
        status: 'published',
        publishedAt: new Date()
      },
      {
        slug: 'business-loan',
        title: 'Business Loan',
        shortDescription: 'Grow your business',
        description: 'Fuel your business growth with flexible financing options. Up to ₹2 Crores available.',
        rate: '11%',
        highlight: false,
        eligibility: ['Business 2+ years', 'ITR Required', 'CIBIL 680+'],
        features: [
          { title: '24hr Approval', description: 'Quick processing' },
          { title: 'Flexible Tenure', description: 'Up to 5 years' },
          { title: 'Pre-Approval Available', description: 'Know your limit' }
        ],
        status: 'published',
        publishedAt: new Date()
      },
      {
        slug: 'credit-cards',
        title: 'Credit Card',
        shortDescription: 'Choose cards from all top banks',
        description: 'Choose from the best credit cards from all top banks with amazing rewards and benefits.',
        rate: 'N/A',
        highlight: true,
        eligibility: ['Age 21-65', 'Min Income ₹15,000/month', 'CIBIL 650+'],
        features: [
          { title: 'Rewards Unlimited', description: 'Cashback & points' },
          { title: 'Pre-Approval Available', description: 'Know your limit' },
          { title: 'Zero Annual Fee', description: 'On select cards' }
        ],
        status: 'published',
        publishedAt: new Date()
      }
    ];
    for (const service of services) {
      await Models.Service.updateOne(
        { slug: service.slug },
        service,
        { upsert: true }
      );
    }
    console.log(`   ✅ Seeded ${services.length} services`);

    // 9. Seed Categories & Tags
    console.log('\n🏷️  Seeding Categories & Tags...');
    const categories = [
      { name: 'Loans', slug: 'loans', type: 'blog', order: 1, published: true },
      { name: 'Credit Score', slug: 'credit-score', type: 'blog', order: 2, published: true },
      { name: 'Financial Tips', slug: 'financial-tips', type: 'blog', order: 3, published: true },
      { name: 'Home Loan', slug: 'home-loan', type: 'service', order: 1, published: true },
      { name: 'Personal Loan', slug: 'personal-loan', type: 'service', order: 2, published: true }
    ];
    for (const cat of categories) {
      await Models.Category.updateOne(
        { slug: cat.slug, type: cat.type },
        { ...cat, publishedAt: new Date() },
        { upsert: true }
      );
    }
    console.log(`   ✅ Seeded ${categories.length} categories`);

    const tags = [
      { name: 'Home Loan', slug: 'home-loan', published: true },
      { name: 'Personal Loan', slug: 'personal-loan', published: true },
      { name: 'Credit Score', slug: 'credit-score', published: true },
      { name: 'Financial Planning', slug: 'financial-planning', published: true },
      { name: 'Loan Tips', slug: 'loan-tips', published: true }
    ];
    for (const tag of tags) {
      await Models.Tag.updateOne(
        { slug: tag.slug },
        { ...tag, publishedAt: new Date() },
        { upsert: true }
      );
    }
    console.log(`   ✅ Seeded ${tags.length} tags`);

    // 10. Seed Testimonials
    console.log('\n💬 Seeding Testimonials...');
    const testimonials = [
      {
        name: 'Rahul Sharma',
        role: 'Home Loan Customer',
        content: 'Excellent service! Got my home loan approved within a week. The team was very helpful and transparent throughout the process.',
        rating: 5,
        featured: true,
        published: true,
        publishedAt: new Date()
      },
      {
        name: 'Priya Gupta',
        role: 'Business Loan Customer',
        content: 'Very professional and knowledgeable team. They helped me find the best interest rate for my business loan. Highly recommended!',
        rating: 5,
        featured: true,
        published: true,
        publishedAt: new Date()
      },
      {
        name: 'Amit Patel',
        role: 'Car Loan Customer',
        content: 'Great experience with Finonest. They made the car loan process so simple. Quick approval and excellent customer support.',
        rating: 5,
        featured: true,
        published: true,
        publishedAt: new Date()
      },
      {
        name: 'Neha Singh',
        role: 'Personal Loan Customer',
        content: 'I was struggling to get a personal loan due to my credit score. Finonest helped me find the right lender. Thank you!',
        rating: 5,
        featured: true,
        published: true,
        publishedAt: new Date()
      }
    ];
    for (const testimonial of testimonials) {
      await Models.Testimonial.updateOne(
        { name: testimonial.name },
        testimonial,
        { upsert: true }
      );
    }
    console.log(`   ✅ Seeded ${testimonials.length} testimonials`);

    // 11. Seed Partners
    console.log('\n🏦 Seeding Banking Partners...');
    const partners = [
      { name: 'HDFC Bank', slug: 'hdfc-bank', featured: true, published: true, publishedAt: new Date() },
      { name: 'ICICI Bank', slug: 'icici-bank', featured: true, published: true, publishedAt: new Date() },
      { name: 'Axis Bank', slug: 'axis-bank', featured: true, published: true, publishedAt: new Date() },
      { name: 'SBI', slug: 'sbi', featured: true, published: true, publishedAt: new Date() },
      { name: 'Kotak Mahindra Bank', slug: 'kotak-mahindra-bank', featured: true, published: true, publishedAt: new Date() },
      { name: 'PNB', slug: 'pnb', featured: true, published: true, publishedAt: new Date() }
    ];
    for (const partner of partners) {
      await Models.Partner.updateOne(
        { slug: partner.slug },
        partner,
        { upsert: true }
      );
    }
    console.log(`   ✅ Seeded ${partners.length} banking partners`);

    // 12. Seed Site Settings
    console.log('\n⚙️  Seeding Site Settings...');
    const siteSettings = {
      siteName: 'Finonest',
      siteTagline: 'Your Trusted Loan Partner',
      primaryPhone: '+91 9876543210',
      primaryEmail: 'info@finonest.com',
      address: '3rd Floor, Besides Jaipur Hospital, BL Tower 1, Tonk Rd, Mahaveer Nagar, Jaipur, Rajasthan 302018',
      whatsappNumber: '919876543210',
      facebookUrl: 'https://facebook.com/finonest',
      instagramUrl: 'https://instagram.com/finonest',
      linkedinUrl: 'https://linkedin.com/company/finonest',
      twitterUrl: 'https://twitter.com/finonest',
      youtubeUrl: 'https://youtube.com/@finonest',
      metaTitle: 'Finonest - Your Trusted Loan Partner | Home Loan, Personal Loan, Car Loan',
      metaDescription: 'Get the best loan rates with 100% paperless processing. Home loans, personal loans, car loans, and more. Quick approval, competitive rates, 50+ bank partners.',
      googleAnalyticsId: '',
      maintenanceMode: false,
      published: true,
      publishedAt: new Date()
    };
    await Models.SiteSettings.updateOne({}, siteSettings, { upsert: true });
    console.log('   ✅ Seeded site settings');

    // 13. Seed Sample Blog Posts
    console.log('\n📝 Seeding Blog Posts...');
    const blogPosts = [
      {
        slug: 'how-to-improve-credit-score-2025',
        title: 'How to Improve Your Credit Score in 2025',
        excerpt: 'Top strategies to boost your credit score and get better loan rates.',
        content: '<p>Your credit score is one of the most important factors when applying for a loan. Here are proven strategies to improve your credit score in 2025...</p>',
        status: 'published',
        publishedAt: new Date(),
        featured: true
      },
      {
        slug: 'home-loan-eligibility-guide',
        title: 'Complete Guide to Home Loan Eligibility',
        excerpt: 'Everything you need to know about home loan eligibility criteria and documents required.',
        content: '<p>Getting a home loan requires meeting certain eligibility criteria. This comprehensive guide covers all aspects of home loan eligibility...</p>',
        status: 'published',
        publishedAt: new Date(),
        featured: true
      }
    ];
    for (const post of blogPosts) {
      await Models.BlogPost.updateOne(
        { slug: post.slug },
        post,
        { upsert: true }
      );
    }
    console.log(`   ✅ Seeded ${blogPosts.length} blog posts`);

    // 14. Seed Sample Pages
    console.log('\n📄 Seeding Pages...');
    const pages = [
      {
        slug: 'home',
        title: 'Home - Finonest',
        template: 'default',
        blocks: [
          {
            type: 'hero',
            props: {
              title: 'Welcome to Finonest',
              subtitle: 'Your trusted partner for all financial solutions',
              ctaText: 'Get Started',
              ctaLink: '/apply'
            }
          },
          {
            type: 'services',
            props: {
              title: 'Our Services',
              services: [
                { title: 'Home Loan', description: 'Instant approval at lowest interest rates', icon: 'home' },
                { title: 'Personal Loan', description: 'Paperless process at low rate', icon: 'user' },
                { title: 'Car Loan', description: 'Drive away your dream car today', icon: 'car' },
                { title: 'Credit Card', description: 'Choose cards from all top banks', icon: 'credit-card' }
              ]
            }
          }
        ],
        status: 'published',
        publishedAt: new Date(),
        seo: {
          metaTitle: 'Finonest - Financial Solutions',
          metaDescription: 'Get instant loans and financial services with Finonest'
        }
      },
      {
        slug: 'about',
        title: 'About Us - Finonest',
        template: 'default',
        blocks: [
          {
            type: 'hero',
            props: {
              title: 'About Finonest',
              subtitle: 'Your Trusted Financial Partner',
              description: 'We are committed to simplifying your financial journey with transparency, speed, and personalized solutions.'
            }
          },
          {
            type: 'content',
            props: {
              title: 'Our Story',
              content: '<p>Finonest is your trusted partner for all financial needs. We simplify the loan process with transparency, speed, and personalized solutions.</p><p>With over 50+ banking partners and a team of experienced professionals, we help you find the best financial products tailored to your needs.</p>'
            }
          },
          {
            type: 'stats',
            props: {
              title: 'Our Impact',
              stats: [
                { label: 'Customers Served', value: '5.8 Lacs', suffix: '+' },
                { label: 'Cities Covered', value: '150', suffix: '+' },
                { label: 'Banking Partners', value: '50', suffix: '+' },
                { label: 'Loans Disbursed', value: '61,000', suffix: 'Cr+' }
              ]
            }
          }
        ],
        status: 'published',
        publishedAt: new Date(),
        seo: {
          metaTitle: 'About Finonest - Your Trusted Financial Partner',
          metaDescription: 'Learn about Finonest, your trusted partner for loans and financial services. 50+ banking partners, transparent process, quick approval.'
        }
      },
    ];
    for (const page of pages) {
      await Models.Page.updateOne(
        { slug: page.slug },
        page,
        { upsert: true }
      );
    }
    console.log(`   ✅ Seeded ${pages.length} pages`);

    console.log('\n✨ CMS Content Seeding Complete!');
    console.log('\n📋 Summary:');
    console.log(`   - SuperAdmin user created`);
    console.log(`   - ${stats.length} Stats`);
    console.log(`   - ${processSteps.length} Process Steps`);
    console.log(`   - ${faqs.length} FAQs`);
    console.log(`   - Navigation Items with dropdown`);
    console.log(`   - Footer content`);
    console.log(`   - ${whyUsFeatures.length} WhyUs Features`);
    console.log(`   - ${services.length} Services`);
    console.log(`   - ${categories.length} Categories`);
    console.log(`   - ${tags.length} Tags`);
    console.log(`   - ${testimonials.length} Testimonials`);
    console.log(`   - ${partners.length} Banking Partners`);
    console.log(`   - Site Settings`);
    console.log(`   - ${blogPosts.length} Blog Posts`);
    console.log(`   - ${pages.length} Pages`);
    console.log('\n🎉 All content has been seeded successfully!');

  } catch (error) {
    console.error('❌ Seeding failed:', error);
    throw error;
  } finally {
    await mongoose.disconnect();
    console.log('\n🔌 Disconnected from MongoDB');
  }
}

// Run seeding
seedCMS().catch(err => {
  console.error('Fatal error:', err);
  process.exit(1);
});
