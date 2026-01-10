import mongoose from 'mongoose';
import bcrypt from 'bcrypt';
import * as Models from './src/models/index.js';

const MONGO_URI = process.env.MONGO_URI || 'mongodb://Fino:Finonest%40yogi%40super@m4w0kcwos8kwgkw0wg8sgk4w:27017/?directConnection=true';

async function seedCompleteData() {
  console.log('🌱 Starting Complete Data Seeding...\n');
  
  try {
    await mongoose.connect(MONGO_URI);
    console.log('✅ Connected to MongoDB\n');

    // Clear all existing data
    console.log('🧹 Clearing existing data...');
    const collections = [
      'users', 'customers', 'otps', 'pages', 'sections', 'services', 'blogposts', 
      'categories', 'tags', 'media', 'formconfigs', 'leads', 'banners', 
      'testimonials', 'partners', 'sitesettings', 'faqs', 'stats', 
      'processsteps', 'navitems', 'footers', 'whyusfeatures'
    ];
    
    for (const collection of collections) {
      try {
        await mongoose.connection.db.collection(collection).deleteMany({});
      } catch (error) {
        console.log(`   ⚠️  Collection ${collection} not found, skipping...`);
      }
    }
    console.log('   ✅ Cleared existing data\n');

    // 1. Create Admin User
    console.log('👤 Creating Admin User...');
    const adminPassword = await bcrypt.hash('Admin@123', 10);
    await Models.User.create({
      email: 'admin@finonest.com',
      name: 'Finonest Admin',
      passwordHash: adminPassword,
      role: 'superadmin',
      isActive: true,
      createdAt: new Date(),
      updatedAt: new Date()
    });
    console.log('   ✅ Created admin user: admin@finonest.com / Admin@123\n');

    // 2. Seed Stats
    console.log('📊 Seeding Stats...');
    const stats = [
      { label: 'Happy Customers', value: '5.8 Lacs', suffix: '+', icon: 'users', order: 1, published: true, publishedAt: new Date() },
      { label: 'Cities Covered', value: '150', suffix: '+', icon: 'map-pin', order: 2, published: true, publishedAt: new Date() },
      { label: 'Partner Banks', value: '50', suffix: '+', icon: 'building', order: 3, published: true, publishedAt: new Date() },
      { label: 'Loans Disbursed', value: '₹61,000', suffix: 'Cr+', icon: 'trending-up', order: 4, published: true, publishedAt: new Date() }
    ];
    await Models.Stat.insertMany(stats);
    console.log(`   ✅ Seeded ${stats.length} stats\n`);

    // 3. Seed Services
    console.log('🏦 Seeding Services...');
    const services = [
      {
        name: 'Home Loan',
        slug: 'home-loan',
        title: 'Home Loan',
        description: 'Get your dream home with our competitive home loan rates starting from 8.5% per annum.',
        shortDescription: 'Instant approval at lowest interest rates',
        interestRate: '8.5% - 12%',
        processingFee: '0.5% - 1%',
        maxAmount: '₹5 Crores',
        tenure: 'Up to 30 years',
        rate: '8.35% p.a.',
        maxTenure: '30 years',
        features: ['Quick approval in 24 hours', 'Minimal documentation', 'Flexible repayment options', 'No prepayment charges'],
        eligibility: ['Age: 21-65 years', 'Minimum income: ₹25,000/month', 'CIBIL score: 650+', 'Employment: 2+ years'],
        documents: ['Income proof', 'Identity proof', 'Address proof', 'Property documents', 'Bank statements'],
        published: true,
        featured: true,
        highlight: true,
        order: 1,
        status: 'published',
        publishedAt: new Date(),
        createdAt: new Date()
      },
      {
        name: 'Personal Loan',
        slug: 'personal-loan',
        title: 'Personal Loan',
        description: 'Instant personal loans for all your needs with minimal documentation and quick approval.',
        shortDescription: 'Paperless process at low rate',
        interestRate: '10.5% - 24%',
        processingFee: '1% - 3%',
        maxAmount: '₹40 Lakhs',
        tenure: 'Up to 7 years',
        rate: '10.49% p.a.',
        maxTenure: '5 years',
        features: ['Instant approval', 'No collateral required', 'Flexible EMI options', 'Online application'],
        eligibility: ['Age: 21-60 years', 'Minimum income: ₹20,000/month', 'CIBIL score: 700+', 'Employment: 1+ years'],
        documents: ['Salary slips', 'Bank statements', 'Identity proof', 'Address proof'],
        published: true,
        featured: true,
        highlight: false,
        order: 2,
        status: 'published',
        publishedAt: new Date(),
        createdAt: new Date()
      },
      {
        name: 'Car Loan',
        slug: 'car-loan',
        title: 'New Car Loan',
        description: 'Drive your dream car today with our attractive car loan offers and easy EMI options.',
        shortDescription: 'Drive away your dream car today',
        interestRate: '7.5% - 14%',
        processingFee: '0.5% - 2%',
        maxAmount: '₹1.5 Crores',
        tenure: 'Up to 7 years',
        rate: '7.99% p.a.',
        maxTenure: '7 years',
        features: ['Up to 90% financing', 'Quick processing', 'Competitive rates', 'Insurance assistance'],
        eligibility: ['Age: 21-65 years', 'Minimum income: ₹20,000/month', 'CIBIL score: 650+', 'Valid driving license'],
        documents: ['Income proof', 'Identity proof', 'Address proof', 'Car quotation', 'Insurance documents'],
        published: true,
        featured: true,
        highlight: false,
        order: 3,
        status: 'published',
        publishedAt: new Date(),
        createdAt: new Date()
      },
      {
        name: 'Business Loan',
        slug: 'business-loan',
        title: 'Business Loan',
        description: 'Grow your business with our flexible business loan solutions tailored for entrepreneurs.',
        shortDescription: 'Fuel your business growth',
        interestRate: '11% - 18%',
        processingFee: '1% - 2%',
        maxAmount: '₹10 Crores',
        tenure: 'Up to 10 years',
        rate: '11% p.a.',
        maxTenure: '5 years',
        features: ['Collateral-free options', 'Quick disbursement', 'Flexible repayment', 'Business advisory support'],
        eligibility: ['Business vintage: 2+ years', 'Annual turnover: ₹10 Lakhs+', 'CIBIL score: 650+', 'ITR filing: 2 years'],
        documents: ['Business registration', 'Financial statements', 'ITR', 'Bank statements', 'GST returns'],
        published: true,
        featured: true,
        highlight: false,
        order: 4,
        status: 'published',
        publishedAt: new Date(),
        createdAt: new Date()
      },
      {
        name: 'Credit Cards',
        slug: 'credit-cards',
        title: 'Credit Card',
        description: 'Choose from a wide range of credit cards with exciting rewards and cashback offers.',
        shortDescription: 'Choose cards from all top banks',
        interestRate: '3.5% per month',
        processingFee: 'Nil to ₹5,000',
        maxAmount: '₹25 Lakhs',
        tenure: 'Revolving credit',
        rate: 'Varies',
        maxTenure: 'Lifetime',
        features: ['Reward points', 'Cashback offers', 'Airport lounge access', 'EMI conversion'],
        eligibility: ['Age: 21-60 years', 'Minimum income: ₹15,000/month', 'CIBIL score: 700+', 'Employment: 1+ years'],
        documents: ['Salary slips', 'Bank statements', 'Identity proof', 'Address proof'],
        published: true,
        featured: false,
        highlight: true,
        order: 5,
        status: 'published',
        publishedAt: new Date(),
        createdAt: new Date()
      }
    ];
    await Models.Service.insertMany(services);
    console.log(`   ✅ Seeded ${services.length} services\n`);

    // 4. Seed Testimonials
    console.log('💬 Seeding Testimonials...');
    const testimonials = [
      {
        name: 'Rajesh Kumar',
        designation: 'Software Engineer',
        company: 'TCS',
        role: 'Home Loan Customer',
        rating: 5,
        review: 'Finonest made my home loan process incredibly smooth. Got approval in just 2 days with competitive rates. Highly recommended!',
        content: 'Excellent service! Got my home loan approved within a week. The team was very helpful and transparent throughout the process.',
        image: '/assets/testimonials/rajesh.jpg',
        published: true,
        featured: true,
        publishedAt: new Date(),
        createdAt: new Date()
      },
      {
        name: 'Priya Sharma',
        designation: 'Marketing Manager',
        company: 'Wipro',
        role: 'Personal Loan Customer',
        rating: 5,
        review: 'Excellent service for personal loan. The team was very professional and guided me through every step. Quick disbursement!',
        content: 'Very professional and knowledgeable team. They helped me find the best interest rate for my business loan. Highly recommended!',
        image: '/assets/testimonials/priya.jpg',
        published: true,
        featured: true,
        publishedAt: new Date(),
        createdAt: new Date()
      },
      {
        name: 'Amit Patel',
        designation: 'Business Owner',
        company: 'Patel Enterprises',
        role: 'Business Loan Customer',
        rating: 4,
        review: 'Got my business loan approved without any hassle. The interest rates are competitive and the process is transparent.',
        content: 'Great experience with Finonest. They made the car loan process so simple. Quick approval and excellent customer support.',
        image: '/assets/testimonials/amit.jpg',
        published: true,
        featured: true,
        publishedAt: new Date(),
        createdAt: new Date()
      },
      {
        name: 'Sneha Reddy',
        designation: 'Doctor',
        company: 'Apollo Hospitals',
        role: 'Car Loan Customer',
        rating: 5,
        review: 'Amazing experience with car loan. The documentation was minimal and the approval was instant. Thank you Finonest!',
        content: 'I was struggling to get a personal loan due to my credit score. Finonest helped me find the right lender. Thank you!',
        image: '/assets/testimonials/sneha.jpg',
        published: true,
        featured: true,
        publishedAt: new Date(),
        createdAt: new Date()
      }
    ];
    await Models.Testimonial.insertMany(testimonials);
    console.log(`   ✅ Seeded ${testimonials.length} testimonials\n`);

    // 5. Seed Banking Partners
    console.log('🏦 Seeding Banking Partners...');
    const partners = [
      { name: 'HDFC Bank', slug: 'hdfc-bank', logo: '/assets/partners/hdfc.png', type: 'bank', published: true, featured: true, order: 1, publishedAt: new Date() },
      { name: 'ICICI Bank', slug: 'icici-bank', logo: '/assets/partners/icici.png', type: 'bank', published: true, featured: true, order: 2, publishedAt: new Date() },
      { name: 'Axis Bank', slug: 'axis-bank', logo: '/assets/partners/axis.png', type: 'bank', published: true, featured: true, order: 3, publishedAt: new Date() },
      { name: 'State Bank of India', slug: 'sbi', logo: '/assets/partners/sbi.png', type: 'bank', published: true, featured: true, order: 4, publishedAt: new Date() },
      { name: 'Kotak Mahindra Bank', slug: 'kotak-mahindra-bank', logo: '/assets/partners/kotak.png', type: 'bank', published: true, featured: true, order: 5, publishedAt: new Date() },
      { name: 'Punjab National Bank', slug: 'pnb', logo: '/assets/partners/pnb.png', type: 'bank', published: true, featured: true, order: 6, publishedAt: new Date() }
    ];
    await Models.Partner.insertMany(partners);
    console.log(`   ✅ Seeded ${partners.length} banking partners\n`);

    // 6. Seed FAQs
    console.log('❓ Seeding FAQs...');
    const faqs = [
      {
        question: 'What is the minimum CIBIL score required for a loan?',
        answer: 'The minimum CIBIL score varies by loan type. For personal loans, we require 700+, while for home and car loans, 650+ is acceptable.',
        category: 'eligibility',
        published: true,
        order: 1,
        publishedAt: new Date(),
        createdAt: new Date()
      },
      {
        question: 'How long does the loan approval process take?',
        answer: 'Our loan approval process is designed to be quick. Personal loans can be approved in 24 hours, while home loans typically take 3-7 working days.',
        category: 'process',
        published: true,
        order: 2,
        publishedAt: new Date(),
        createdAt: new Date()
      },
      {
        question: 'What documents are required for loan application?',
        answer: 'Basic documents include identity proof, address proof, income proof, and bank statements. Specific requirements vary by loan type.',
        category: 'documents',
        published: true,
        order: 3,
        publishedAt: new Date(),
        createdAt: new Date()
      },
      {
        question: 'Can I prepay my loan without charges?',
        answer: 'Yes, most of our loan products allow prepayment without charges. However, terms may vary by lender and loan type.',
        category: 'repayment',
        published: true,
        order: 4,
        publishedAt: new Date(),
        createdAt: new Date()
      },
      {
        question: 'Is there any processing fee for loans?',
        answer: 'Processing fees vary by loan type and amount. We offer competitive processing fees starting from 0.5% of the loan amount.',
        category: 'fees',
        published: true,
        order: 5,
        publishedAt: new Date(),
        createdAt: new Date()
      }
    ];
    await Models.FAQ.insertMany(faqs);
    console.log(`   ✅ Seeded ${faqs.length} FAQs\n`);

    // 7. Seed Process Steps
    console.log('🔄 Seeding Process Steps...');
    const processSteps = [
      {
        title: 'Apply Online',
        description: 'Fill out our simple online application form with basic details',
        icon: 'file-text',
        order: 1,
        published: true,
        publishedAt: new Date(),
        createdAt: new Date()
      },
      {
        title: 'Document Verification',
        description: 'Upload required documents for quick verification',
        icon: 'check-circle',
        order: 2,
        published: true,
        publishedAt: new Date(),
        createdAt: new Date()
      },
      {
        title: 'Quick Approval',
        description: 'Get instant approval with competitive interest rates',
        icon: 'thumbs-up',
        order: 3,
        published: true,
        publishedAt: new Date(),
        createdAt: new Date()
      },
      {
        title: 'Loan Disbursement',
        description: 'Receive funds directly in your bank account',
        icon: 'credit-card',
        order: 4,
        published: true,
        publishedAt: new Date(),
        createdAt: new Date()
      }
    ];
    await Models.ProcessStep.insertMany(processSteps);
    console.log(`   ✅ Seeded ${processSteps.length} process steps\n`);

    // 8. Seed Why Choose Us Features
    console.log('⭐ Seeding Why Choose Us Features...');
    const whyUsFeatures = [
      {
        title: 'Quick Approval',
        description: 'Get loan approval in as fast as 24 hours with minimal documentation',
        icon: 'zap',
        order: 1,
        published: true,
        publishedAt: new Date(),
        createdAt: new Date()
      },
      {
        title: 'Best Interest Rates',
        description: 'Competitive interest rates starting from 8.5% per annum',
        icon: 'percent',
        order: 2,
        published: true,
        publishedAt: new Date(),
        createdAt: new Date()
      },
      {
        title: '50+ Bank Partners',
        description: 'Wide network of banking partners to get you the best deals',
        icon: 'users',
        order: 3,
        published: true,
        publishedAt: new Date(),
        createdAt: new Date()
      },
      {
        title: '24/7 Support',
        description: 'Round-the-clock customer support for all your queries',
        icon: 'headphones',
        order: 4,
        published: true,
        publishedAt: new Date(),
        createdAt: new Date()
      },
      {
        title: 'Simple Process',
        description: 'Hassle-free application process with minimal paperwork',
        icon: 'file-text',
        order: 5,
        published: true,
        publishedAt: new Date(),
        createdAt: new Date()
      },
      {
        title: '100% Transparent',
        description: 'No hidden charges, complete transparency in all dealings',
        icon: 'eye',
        order: 6,
        published: true,
        publishedAt: new Date(),
        createdAt: new Date()
      }
    ];
    await Models.WhyUsFeature.insertMany(whyUsFeatures);
    console.log(`   ✅ Seeded ${whyUsFeatures.length} why choose us features\n`);

    // 9. Seed Navigation Items
    console.log('🧭 Seeding Navigation Items...');
    const parentNavItems = [
      { label: 'Home', href: '/', position: 'header', order: 1, published: true, publishedAt: new Date() },
      { label: 'About Us', href: '/about', position: 'header', order: 2, published: true, publishedAt: new Date() },
      { label: 'Services', href: '/services', position: 'header', order: 3, published: true, publishedAt: new Date() },
      { label: 'Blog', href: '/blog', position: 'header', order: 4, published: true, publishedAt: new Date() },
      { label: 'Contact', href: '/contact', position: 'header', order: 5, published: true, publishedAt: new Date() },
      { label: 'Apply Now', href: '/apply', position: 'header', order: 6, published: true, highlight: true, publishedAt: new Date() }
    ];
    await Models.NavItem.insertMany(parentNavItems);
    console.log(`   ✅ Seeded ${parentNavItems.length} navigation items\n`);

    // 10. Seed Footer
    console.log('🦶 Seeding Footer...');
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
    await Models.Footer.create(footer);
    console.log('   ✅ Seeded footer content\n');

    // 11. Seed Categories & Tags
    console.log('🏷️ Seeding Categories & Tags...');
    const categories = [
      { name: 'Loans', slug: 'loans', type: 'blog', order: 1, published: true, publishedAt: new Date() },
      { name: 'Credit Score', slug: 'credit-score', type: 'blog', order: 2, published: true, publishedAt: new Date() },
      { name: 'Financial Tips', slug: 'financial-tips', type: 'blog', order: 3, published: true, publishedAt: new Date() },
      { name: 'Home Loan', slug: 'home-loan', type: 'service', order: 1, published: true, publishedAt: new Date() },
      { name: 'Personal Loan', slug: 'personal-loan', type: 'service', order: 2, published: true, publishedAt: new Date() }
    ];
    await Models.Category.insertMany(categories);
    
    const tags = [
      { name: 'Home Loan', slug: 'home-loan', published: true, publishedAt: new Date() },
      { name: 'Personal Loan', slug: 'personal-loan', published: true, publishedAt: new Date() },
      { name: 'Credit Score', slug: 'credit-score', published: true, publishedAt: new Date() },
      { name: 'Financial Planning', slug: 'financial-planning', published: true, publishedAt: new Date() },
      { name: 'Loan Tips', slug: 'loan-tips', published: true, publishedAt: new Date() }
    ];
    await Models.Tag.insertMany(tags);
    console.log(`   ✅ Seeded ${categories.length} categories and ${tags.length} tags\n`);

    // 12. Seed Site Settings
    console.log('⚙️ Seeding Site Settings...');
    const siteSettings = {
      siteName: 'Finonest',
      siteTagline: 'Your Trusted Loan Partner',
      primaryPhone: '+91 94625 53887',
      primaryEmail: 'info@finonest.com',
      address: '3rd Floor, Besides Jaipur Hospital, BL Tower 1, Tonk Rd, Mahaveer Nagar, Jaipur, Rajasthan 302018',
      whatsappNumber: '919462553887',
      facebookUrl: 'https://facebook.com/finonest',
      instagramUrl: 'https://instagram.com/finonest.india',
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
    await Models.SiteSettings.create(siteSettings);
    console.log('   ✅ Seeded site settings\n');

    // 13. Seed Sample Blog Posts
    console.log('📝 Seeding Blog Posts...');
    const blogPosts = [
      {
        slug: 'how-to-improve-credit-score-2025',
        title: 'How to Improve Your Credit Score in 2025',
        excerpt: 'Top strategies to boost your credit score and get better loan rates.',
        content: '<p>Your credit score is one of the most important factors when applying for a loan. Here are proven strategies to improve your credit score in 2025...</p>',
        status: 'published',
        published: true,
        publishedAt: new Date(),
        featured: true,
        createdAt: new Date(),
        updatedAt: new Date()
      },
      {
        slug: 'home-loan-eligibility-guide',
        title: 'Complete Guide to Home Loan Eligibility',
        excerpt: 'Everything you need to know about home loan eligibility criteria and documents required.',
        content: '<p>Getting a home loan requires meeting certain eligibility criteria. This comprehensive guide covers all aspects of home loan eligibility...</p>',
        status: 'published',
        published: true,
        publishedAt: new Date(),
        featured: true,
        createdAt: new Date(),
        updatedAt: new Date()
      }
    ];
    await Models.BlogPost.insertMany(blogPosts);
    console.log(`   ✅ Seeded ${blogPosts.length} blog posts\n`);

    // 14. Seed Sample Pages
    console.log('📄 Seeding Pages...');
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
        published: true,
        publishedAt: new Date(),
        seo: {
          metaTitle: 'Finonest - Financial Solutions',
          metaDescription: 'Get instant loans and financial services with Finonest'
        },
        createdAt: new Date(),
        updatedAt: new Date()
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
          }
        ],
        status: 'published',
        published: true,
        publishedAt: new Date(),
        seo: {
          metaTitle: 'About Finonest - Your Trusted Financial Partner',
          metaDescription: 'Learn about Finonest, your trusted partner for loans and financial services. 50+ banking partners, transparent process, quick approval.'
        },
        createdAt: new Date(),
        updatedAt: new Date()
      }
    ];
    await Models.Page.insertMany(pages);
    console.log(`   ✅ Seeded ${pages.length} pages\n`);

    console.log('✨ Complete Data Seeding Finished!\n');
    console.log('📋 Summary:');
    console.log('   ✅ 1 Admin User');
    console.log(`   ✅ ${stats.length} Stats`);
    console.log(`   ✅ ${services.length} Services`);
    console.log(`   ✅ ${testimonials.length} Testimonials`);
    console.log(`   ✅ ${partners.length} Banking Partners`);
    console.log(`   ✅ ${faqs.length} FAQs`);
    console.log(`   ✅ ${processSteps.length} Process Steps`);
    console.log(`   ✅ ${whyUsFeatures.length} Why Choose Us Features`);
    console.log(`   ✅ ${parentNavItems.length} Navigation Items`);
    console.log('   ✅ 1 Footer Configuration');
    console.log(`   ✅ ${categories.length} Categories & ${tags.length} Tags`);
    console.log('   ✅ 1 Site Settings Configuration');
    console.log(`   ✅ ${blogPosts.length} Blog Posts`);
    console.log(`   ✅ ${pages.length} Pages`);
    console.log('\n🎉 All data has been seeded successfully!');
    console.log('\n📝 Login Credentials:');
    console.log('   Email: admin@finonest.com');
    console.log('   Password: Admin@123');

  } catch (error) {
    console.error('❌ Seeding failed:', error);
    throw error;
  } finally {
    await mongoose.disconnect();
    console.log('\n🔌 Disconnected from MongoDB');
  }
}

// Run seeding
seedCompleteData().catch(err => {
  console.error('Fatal error:', err);
  process.exit(1);
});