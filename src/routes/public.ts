import { Router } from 'express';
import { getPageBySlug, listPages } from '../controllers/page.controller';
import { listServices, getServiceBySlug } from '../controllers/service.controller';
import { listPosts, getPostBySlug } from '../controllers/blog.controller';
import { listMediaPublic, getMediaById } from '../controllers/media.controller';
import { listTestimonials, getTestimonial } from '../controllers/testimonial.controller';
import { listPartners, getPartnerBySlug } from '../controllers/partner.controller';
import { listBanners } from '../controllers/banner.controller';
import { getSiteSettings } from '../controllers/settings.controller';
import { submitForm } from '../controllers/form.controller';
import { listFAQs, getFAQ } from '../controllers/faq.controller';
import { listStats } from '../controllers/stat.controller';
import { listProcessSteps } from '../controllers/processStep.controller';
import { listNavItems } from '../controllers/navItem.controller';
import { getFooter } from '../controllers/footer.controller';
import { calculateEMI } from '../controllers/emi.controller';
import { listWhyUsFeatures } from '../controllers/whyUsFeature.controller';
import { attachCustomer } from '../middleware/customer-auth';
import * as Models from '../models';
import { listModel } from '../controllers/simple.controller';

const router = Router();

// Pages
router.get('/pages/:slug', getPageBySlug);
router.get('/pages', listPages);

// Services
router.get('/services', listServices);
router.get('/services/:slug', getServiceBySlug);

// Blog
router.get('/blog', listPosts);
router.get('/blog/:slug', getPostBySlug);

// Categories & Tags
router.get('/categories', listModel(Models.Category));
router.get('/tags', listModel(Models.Tag));

// Media
router.get('/media', listMediaPublic);
router.get('/media/:id', getMediaById);

// Testimonials
router.get('/testimonials', listTestimonials);
router.get('/testimonials/:id', getTestimonial);

// Partners
router.get('/partners', listPartners);
router.get('/partners/:slug', getPartnerBySlug);

// Banners
router.get('/banners', listBanners);

// Site Settings (singleton)
router.get('/settings', getSiteSettings);

// Form submissions (attach customer if authenticated, but allow anonymous)
router.post('/forms/:slug/submit', attachCustomer, submitForm);

// FAQ
router.get('/faqs', listFAQs);
router.get('/faqs/:id', getFAQ);

// Stats
router.get('/stats', listStats);

// Process Steps
router.get('/process-steps', listProcessSteps);

// Navigation
router.get('/nav-items', listNavItems);

// Footer (singleton)
router.get('/footer', getFooter);

// EMI Calculator
router.post('/emi/calculate', calculateEMI);

// WhyUs Features
router.get('/why-us-features', listWhyUsFeatures);

export default router;
