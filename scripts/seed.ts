import mongoose from 'mongoose';
import bcrypt from 'bcrypt';
import User from '../src/models/user.model';
import Page from '../src/models/page.model';
import Service from '../src/models/service.model';
import BlogPost from '../src/models/blog.model';
import Partner from '../src/models/partner.model';
import Testimonial from '../src/models/testimonial.model';
import SiteSettings from '../src/models/settings.model';

const MONGO_URI = process.env.MONGO_URI || 'mongodb://localhost:27017/finonest-dev';

async function seed() {
  await mongoose.connect(MONGO_URI, { } as any);

  // SuperAdmin user
  const adminEmail = process.env.SEED_ADMIN_EMAIL || 'admin@finonest.local';
  const adminPassword = process.env.SEED_ADMIN_PASSWORD || 'ChangeMe123!';
  const existing = await User.findOne({ email: adminEmail });
  if (!existing) {
    const passwordHash = await bcrypt.hash(adminPassword, 10);
    await User.create({ email: adminEmail, name: 'Super Admin', passwordHash, role: 'superadmin' });
    console.log('Created SuperAdmin:', adminEmail);
  } else {
    console.log('SuperAdmin already exists:', adminEmail);
  }

  // Site settings with complete data
  await SiteSettings.updateOne({}, {
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
    maintenanceMode: false,
    // Legacy fields for backward compatibility
    siteTitle: 'Finonest',
    siteDescription: 'Smart loans, simple process',
    contactInfo: { phone: '+91 9876543210', email: 'contact@finonest.com' }
  }, { upsert: true });

  // Home Page with complete blocks and data
  await Page.updateOne({ slug: '/' }, {
    slug: '/', 
    title: 'Finonest - Your Trusted Loan Partner', 
    template: 'home', 
    status: 'published', 
    blocks: [
      { 
        type: 'hero', 
        props: { 
          badgeText: "India's Fastest Growing Loan Provider", 
          heading: 'Your Financial Dreams, Simplified', 
          subheading: 'Get instant loan approvals with competitive rates. From personal loans to home financing, we make your dreams come true with 100% paperless processing.', 
          heroImage: 'https://images.unsplash.com/photo-1560472354-b33ff0c44a43?w=800&q=80',
          ctas: [{ label: 'Apply Now', link: '/apply' }, { label: 'Check Eligibility', link: '/services' }]
        } 
      },
      { 
        type: 'creditScoreBanner', 
        props: { 
          title: 'Check Your Credit Score for Free', 
          subtitle: 'Know your creditworthiness instantly and improve your loan eligibility',
          backgroundImage: 'https://images.unsplash.com/photo-1551288049-bebda4e38f71?w=800&q=80'
        } 
      },
      { 
        type: 'services', 
        props: { 
          title: 'Our Loan Services', 
          subtitle: 'Choose from our wide range of financial products designed for your needs',
          services: [
            { 
              title: 'Personal Loan', 
              description: 'Quick personal loans up to ₹50 lakhs with minimal documentation. Get funds for any personal need within 24 hours.', 
              icon: '💰', 
              rate: '10.5% onwards', 
              image: 'https://images.unsplash.com/photo-1554224155-6726b3ff858f?w=400&q=80',
              features: ['No collateral required', 'Flexible tenure', 'Quick approval']
            },
            { 
              title: 'Home Loan', 
              description: 'Affordable home loans with attractive interest rates. Make your dream home a reality with easy EMIs.', 
              icon: '🏠', 
              rate: '8.5% onwards', 
              image: 'https://images.unsplash.com/photo-1560518883-ce09059eeffa?w=400&q=80',
              features: ['Up to 90% funding', 'Long tenure options', 'Tax benefits']
            },
            { 
              title: 'Business Loan', 
              description: 'Grow your business with flexible business loans. Working capital and expansion funding made easy.', 
              icon: '🏢', 
              rate: '12% onwards', 
              image: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=400&q=80',
              features: ['Collateral-free options', 'Quick disbursement', 'Flexible repayment']
            },
            { 
              title: 'Car Loan', 
              description: 'Drive your dream car today with our attractive car loan offers. New and used car financing available.', 
              icon: '🚗', 
              rate: '9.5% onwards', 
              image: 'https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?w=400&q=80',
              features: ['Up to 100% funding', 'Instant approval', 'Competitive rates']
            },
            { 
              title: 'Loan Against Property', 
              description: 'Unlock the value of your property with our LAP solutions. High loan amounts at attractive rates.', 
              icon: '🏘️', 
              rate: '11% onwards', 
              image: 'https://images.unsplash.com/photo-1560518883-ce09059eeffa?w=400&q=80',
              features: ['High loan amount', 'Long tenure', 'Minimal documentation']
            },
            { 
              title: 'Credit Cards', 
              description: 'Premium credit cards with exclusive benefits, rewards, and cashback offers for all your needs.', 
              icon: '💳', 
              rate: 'Lifetime free', 
              image: 'https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?w=400&q=80',
              features: ['Reward points', 'Cashback offers', 'Premium benefits']
            }
          ]
        } 
      },
      { 
        type: 'processSteps', 
        props: { 
          title: 'Get Your Loan in 4 Simple Steps', 
          subtitle: 'Our streamlined process ensures quick and hassle-free loan approval',
          steps: [
            { 
              step: 1, 
              title: 'Apply Online', 
              description: 'Fill our simple online application form with basic details. Takes less than 5 minutes to complete.', 
              icon: '📝', 
              image: 'https://images.unsplash.com/photo-1586281380349-632531db7ed4?w=400&q=80'
            },
            { 
              step: 2, 
              title: 'Document Upload', 
              description: 'Upload required documents digitally through our secure platform. No physical paperwork needed.', 
              icon: '📄', 
              image: 'https://images.unsplash.com/photo-1554224155-8d04cb21cd6c?w=400&q=80'
            },
            { 
              step: 3, 
              title: 'Quick Approval', 
              description: 'Get instant approval notification within 30 minutes. Our AI-powered system ensures fast processing.', 
              icon: '✅', 
              image: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=400&q=80'
            },
            { 
              step: 4, 
              title: 'Fund Transfer', 
              description: 'Receive funds directly in your bank account within 24 hours of approval. No delays, no hassles.', 
              icon: '💸', 
              image: 'https://images.unsplash.com/photo-1579621970563-ebec7560ff3e?w=400&q=80'
            }
          ]
        } 
      },
      { 
        type: 'whyUs', 
        props: { 
          title: 'Why Choose Finonest?', 
          subtitle: 'Your trusted financial partner with unmatched benefits and customer-first approach',
          features: [
            { 
              title: 'Lightning Fast Approval', 
              description: 'Get loan approval in just 30 minutes with our AI-powered assessment system. No long waiting periods.', 
              icon: '⚡', 
              image: 'https://images.unsplash.com/photo-1551288049-bebda4e38f71?w=400&q=80'
            },
            { 
              title: 'Best Interest Rates', 
              description: 'Enjoy the most competitive interest rates in the market. We negotiate with 50+ lenders for your benefit.', 
              icon: '💎', 
              image: 'https://images.unsplash.com/photo-1579621970563-ebec7560ff3e?w=400&q=80'
            },
            { 
              title: '100% Digital Process', 
              description: 'Complete paperless loan process from application to disbursement. Everything happens online securely.', 
              icon: '📱', 
              image: 'https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?w=400&q=80'
            },
            { 
              title: 'Transparent Pricing', 
              description: 'No hidden charges or surprise fees. What you see is what you pay. Complete transparency in all dealings.', 
              icon: '🔍', 
              image: 'https://images.unsplash.com/photo-1554224155-8d04cb21cd6c?w=400&q=80'
            },
            { 
              title: '24/7 Customer Support', 
              description: 'Round-the-clock customer support to assist you at every step. Our experts are always ready to help.', 
              icon: '🎧', 
              image: 'https://images.unsplash.com/photo-1556745757-8d76bdb6984b?w=400&q=80'
            },
            { 
              title: 'Flexible Repayment', 
              description: 'Choose from multiple repayment options that suit your financial situation. EMI holidays available.', 
              icon: '🔄', 
              image: 'https://images.unsplash.com/photo-1554224154-26032fced8bd?w=400&q=80'
            }
          ]
        } 
      },
      { 
        type: 'testimonials', 
        props: { 
          title: 'What Our Customers Say', 
          subtitle: 'Over 50,000+ satisfied customers across India trust Finonest for their financial needs',
          testimonials: [
            { 
              name: 'Ravi Kumar', 
              role: 'Business Owner, Mumbai', 
              content: 'Finonest helped me get a business loan of ₹25 lakhs in just 2 days. The process was completely digital and hassle-free. Highly recommended for quick business funding!', 
              rating: 5, 
              avatar: 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=100&q=80'
            },
            { 
              name: 'Priya Sharma', 
              role: 'Software Engineer, Bangalore', 
              content: 'Got my home loan approved in just 48 hours with the best interest rate in the market. The team was very supportive throughout the process. Thank you Finonest!', 
              rating: 5, 
              avatar: 'https://images.unsplash.com/photo-1494790108755-2616b612b786?w=100&q=80'
            },
            { 
              name: 'Amit Patel', 
              role: 'Marketing Manager, Delhi', 
              content: 'Personal loan of ₹5 lakhs approved instantly! No paperwork, no branch visits. Everything was done online. Finonest made it so simple and convenient.', 
              rating: 5, 
              avatar: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=100&q=80'
            },
            { 
              name: 'Sneha Reddy', 
              role: 'Doctor, Hyderabad', 
              content: 'Excellent service and competitive rates. Got my car loan approved with minimal documentation. The customer support team is very responsive and helpful.', 
              rating: 5, 
              avatar: 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=100&q=80'
            }
          ]
        } 
      },
      { 
        type: 'partnerBanks', 
        props: { 
          title: 'Our Banking Partners', 
          subtitle: 'Partnered with 50+ leading banks and NBFCs to offer you the best loan deals',
          partners: [
            { name: 'HDFC Bank', logo: '/assets/hdfc.png', featured: true },
            { name: 'ICICI Bank', logo: '/assets/icici_bank.png', featured: true },
            { name: 'Axis Bank', logo: '/assets/axis-bank.png', featured: true },
            { name: 'Kotak Bank', logo: '/assets/kotak.png', featured: true },
            { name: 'SBI', logo: '/assets/SBI.png', featured: true },
            { name: 'Yes Bank', logo: '/assets/yes_bank.png', featured: false },
            { name: 'IDFC First Bank', logo: '/assets/IDFC_FIRST.png', featured: false },
            { name: 'IndusInd Bank', logo: '/assets/INDUSIND.png', featured: false }
          ]
        } 
      },
      { 
        type: 'faq', 
        props: { 
          title: 'Frequently Asked Questions', 
          subtitle: 'Get answers to common loan-related queries from our experts',
          faqs: [
            { 
              question: 'How long does loan approval take?', 
              answer: 'Most loans are approved within 30 minutes to 24 hours of document submission. Personal loans can be approved instantly, while secured loans may take up to 48 hours for verification.'
            },
            { 
              question: 'What documents are required for loan application?', 
              answer: 'Basic documents include ID proof (Aadhar/PAN), address proof, income proof (salary slips/ITR), and bank statements for last 3-6 months. Additional documents may be required based on loan type.'
            },
            { 
              question: 'What is the minimum credit score required?', 
              answer: 'We accept applications from credit scores of 650 and above. However, higher credit scores (750+) get better interest rates and higher loan amounts. We also consider other factors for approval.'
            },
            { 
              question: 'Are there any hidden charges or processing fees?', 
              answer: 'We believe in complete transparency. All charges including processing fees, prepayment charges, and other applicable fees are clearly mentioned upfront. No hidden charges whatsoever.'
            },
            { 
              question: 'Can I prepay my loan without penalty?', 
              answer: 'Yes, you can prepay your loan partially or fully. For floating rate loans, there are no prepayment charges. For fixed rate loans, minimal charges may apply as per RBI guidelines.'
            },
            { 
              question: 'How is the interest rate determined?', 
              answer: 'Interest rates are determined based on your credit score, income, loan amount, tenure, and current market rates. We offer the most competitive rates by comparing offers from 50+ lenders.'
            }
          ]
        } 
      },
      { 
        type: 'contact', 
        props: { 
          title: 'Need Help? Contact Our Loan Experts', 
          subtitle: 'Our certified financial advisors are available 24/7 to help you choose the right loan',
          phone: '+91 9876543210',
          email: 'contact@finonest.com',
          address: '3rd Floor, Besides Jaipur Hospital, BL Tower 1, Tonk Rd, Mahaveer Nagar, Jaipur, Rajasthan 302018',
          mapUrl: 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3558.7!2d75.8!3d26.9!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMjbCsDU0JzAwLjAiTiA3NcKwNDgnMDAuMCJF!5e0!3m2!1sen!2sin!4v1234567890'
        } 
      }
    ],
    seo: {
      metaTitle: 'Finonest - Your Trusted Loan Partner | Best Loan Rates in India',
      metaDescription: 'Get the best loan rates with 100% paperless processing. Personal loans, home loans, business loans with instant approval. Apply now at Finonest.'
    }
  }, { upsert: true });

  // About Page
  await Page.updateOne({ slug: '/about' }, {
    slug: '/about',
    title: 'About Finonest - Your Trusted Financial Partner',
    template: 'default',
    status: 'published',
    blocks: [
      { 
        type: 'hero', 
        props: { 
          heading: 'About Finonest', 
          subheading: 'Established in 2020, Finonest has been revolutionizing the lending industry in India with innovative financial solutions and customer-centric approach.' 
        } 
      },
      { 
        type: 'whyUs', 
        props: { 
          title: 'Our Mission & Vision', 
          subtitle: 'Empowering financial dreams with transparent, accessible, and affordable lending solutions for every Indian.' 
        } 
      },
      { type: 'testimonials', props: { title: 'Customer Success Stories', subtitle: 'Real experiences from our valued customers' } }
    ],
    seo: {
      metaTitle: 'About Finonest - Leading Loan Provider in India | Our Story',
      metaDescription: 'Learn about Finonest, India\'s trusted loan provider since 2020. Discover our mission to make financial dreams accessible with transparent lending solutions.'
    }
  }, { upsert: true });

  // Services Page
  await Page.updateOne({ slug: '/services' }, {
    slug: '/services',
    title: 'Loan Services - Personal, Home, Business Loans | Finonest',
    template: 'default',
    status: 'published',
    blocks: [
      { 
        type: 'hero', 
        props: { 
          heading: 'Our Loan Services', 
          subheading: 'Comprehensive range of loan products designed to meet all your financial needs with competitive interest rates and flexible terms.' 
        } 
      },
      { type: 'services', props: { title: 'Choose Your Loan Type', subtitle: 'Tailored solutions for every financial requirement' } },
      { type: 'processSteps', props: { title: 'Simple Application Process', subtitle: 'Get approved in minutes, not days' } },
      { type: 'whyUs', props: { title: 'Why Our Loans Stand Out', subtitle: 'Competitive rates, quick approval, and excellent service' } }
    ],
    seo: {
      metaTitle: 'Loan Services - Personal, Home, Business Loans at Best Rates | Finonest',
      metaDescription: 'Explore Finonest\'s comprehensive loan services including personal loans, home loans, business loans, and more at competitive interest rates.'
    }
  }, { upsert: true });

  // Contact Page
  await Page.updateOne({ slug: '/contact' }, {
    slug: '/contact',
    title: 'Contact Finonest - Get Expert Loan Advice',
    template: 'default',
    status: 'published',
    blocks: [
      { 
        type: 'hero', 
        props: { 
          heading: 'Contact Our Loan Experts', 
          subheading: 'Get personalized loan advice from our certified financial experts. We\'re here to help you make the right financial decisions.' 
        } 
      },
      { type: 'contact', props: { title: 'Get in Touch', subtitle: 'Multiple ways to reach us - call, email, or visit our office' } }
    ],
    seo: {
      metaTitle: 'Contact Finonest - Expert Loan Consultation | Get Help Now',
      metaDescription: 'Contact Finonest for expert loan consultation. Our certified advisors are available 24/7 to help you find the best loan solutions.'
    }
  }, { upsert: true });

  // EMI Calculator Page
  await Page.updateOne({ slug: '/emi-calculator' }, {
    slug: '/emi-calculator',
    title: 'EMI Calculator - Calculate Your Loan EMI | Finonest',
    template: 'default',
    status: 'published',
    blocks: [
      { 
        type: 'hero', 
        props: { 
          heading: 'EMI Calculator', 
          subheading: 'Calculate your Equated Monthly Installments (EMI) for home loans, personal loans, car loans, and more. Plan your finances better.' 
        } 
      },
      { type: 'faq', props: { title: 'EMI Calculator FAQs', subtitle: 'Common questions about EMI calculations' } }
    ],
    seo: {
      metaTitle: 'EMI Calculator - Calculate Home, Personal, Car Loan EMI | Finonest',
      metaDescription: 'Use Finonest\'s free EMI calculator to calculate monthly installments for home loans, personal loans, car loans with accurate results.'
    }
  }, { upsert: true });

  // Credit Score Page
  await Page.updateOne({ slug: '/credit-score' }, {
    slug: '/credit-score',
    title: 'Free Credit Score Check - Know Your CIBIL Score | Finonest',
    template: 'default',
    status: 'published',
    blocks: [
      { 
        type: 'hero', 
        props: { 
          heading: 'Check Your Credit Score for Free', 
          subheading: 'Get your CIBIL score instantly and understand your creditworthiness. Monitor your credit health and improve your loan eligibility.' 
        } 
      },
      { type: 'creditScoreBanner', props: { title: 'Free Credit Report', subtitle: 'Detailed credit analysis and improvement tips' } },
      { type: 'faq', props: { title: 'Credit Score FAQs', subtitle: 'Everything you need to know about credit scores' } }
    ],
    seo: {
      metaTitle: 'Free Credit Score Check - CIBIL Score Report | Finonest',
      metaDescription: 'Check your credit score for free with Finonest. Get instant CIBIL score, detailed credit report, and tips to improve your creditworthiness.'
    }
  }, { upsert: true });

  // Terms and Conditions Page
  await Page.updateOne({ slug: '/terms-and-conditions' }, {
    slug: '/terms-and-conditions',
    title: 'Terms and Conditions - Finonest Loan Services',
    template: 'default',
    status: 'published',
    blocks: [
      { 
        type: 'hero', 
        props: { 
          heading: 'Terms and Conditions', 
          subheading: 'Please read these terms and conditions carefully before using our loan services. These terms govern your use of Finonest platform.' 
        } 
      }
    ],
    seo: {
      metaTitle: 'Terms and Conditions - Finonest Loan Services',
      metaDescription: 'Read Finonest\'s terms and conditions for loan services. Understand our policies, user agreements, and service terms.'
    }
  }, { upsert: true });

  // Privacy Policy Page
  await Page.updateOne({ slug: '/privacy-policy' }, {
    slug: '/privacy-policy',
    title: 'Privacy Policy - How We Protect Your Data | Finonest',
    template: 'default',
    status: 'published',
    blocks: [
      { 
        type: 'hero', 
        props: { 
          heading: 'Privacy Policy', 
          subheading: 'Your privacy is important to us. Learn how we collect, use, and protect your personal information when you use our loan services.' 
        } 
      }
    ],
    seo: {
      metaTitle: 'Privacy Policy - Data Protection & Security | Finonest',
      metaDescription: 'Finonest privacy policy explains how we collect, use, and protect your personal data. Learn about our commitment to data security.'
    }
  }, { upsert: true });

  // Sample Service
  await Service.updateOne({ slug: 'home-loan' }, { slug: 'home-loan', title: 'Home Loan', shortDescription: 'Turn your dream home into reality', rate: '6.5%', status: 'published' }, { upsert: true });

  // Sample Blog
  await BlogPost.updateOne({ slug: 'improve-credit-score-2025' }, {
    slug: 'improve-credit-score-2025', title: 'How to Improve Your Credit Score in 2025', excerpt: 'Top strategies to boost your credit score.', content: '<p>Full article HTML...</p>', status: 'published', publishedAt: new Date()
  }, { upsert: true });

  // Partner
  await Partner.updateOne({ slug: 'icici-bank' }, { slug: 'icici-bank', name: 'ICICI Bank', featured: true, applyLink: 'https://icici.example.com/apply', status: 'published' }, { upsert: true });

  // Testimonial
  await Testimonial.updateOne({ name: 'Ravi Kumar' }, { name: 'Ravi Kumar', role: 'Verified Customer', content: 'Finonest helped me get a home loan in 30 days.', rating: 5, published: true }, { upsert: true });

  console.log('Seeding complete');
  await mongoose.disconnect();
}

seed().catch(err => { console.error(err); process.exit(1); });
