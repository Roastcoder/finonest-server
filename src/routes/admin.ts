import { Router } from 'express';
import { requireAuth } from '../middleware/auth';
import { requireRole } from '../middleware/roles';
import { listPages, createPage, updatePage, deletePage, publishPage, schedulePage } from '../controllers/page.controller';
import { listServices, createService, updateService, deleteService, publishService, scheduleService } from '../controllers/service.controller';
import { listPosts, createPost, updatePost, deletePost, publishPost, schedulePost } from '../controllers/blog.controller';
import { getSiteSettings } from '../controllers/settings.controller';
import { listUsers, getUser, createUser, updateUser, deleteUser } from '../controllers/user.controller';
import * as Models from '../models';
import { listModel, getModel, createModel, updateModel, deleteModel } from '../controllers/simple.controller';
import { bannerSchema } from '../validators/banner.validator';
import { testimonialSchema } from '../validators/testimonial.validator';
import { validateBody } from '../middleware/validation';
import { pageSchema } from '../validators/page.validator';
import { serviceSchema } from '../validators/service.validator';
import { blogSchema } from '../validators/blog.validator';

const router = Router();

// All admin routes require auth
router.use(requireAuth);

// Check role endpoint
router.get('/check-role', (req, res) => {
  const user = (req as any).user;
  const isAdmin = user && ['admin', 'superadmin', 'editor'].includes(user.role);
  return res.json({ status: 'ok', data: { isAdmin } });
});

// Pages
router.get('/pages', requireRole('editor', 'admin', 'superadmin'), listPages);
router.post('/pages', requireRole('editor', 'admin', 'superadmin'), validateBody(pageSchema), createPage);
router.patch('/pages/:id', requireRole('editor', 'admin', 'superadmin'), validateBody(pageSchema), updatePage);
router.delete('/pages/:id', requireRole('admin', 'superadmin'), deletePage);
router.post('/pages/:id/publish', requireRole('admin', 'superadmin', 'editor'), publishPage);
router.post('/pages/:id/schedule', requireRole('admin', 'superadmin', 'editor'), schedulePage);

// Services
router.get('/services', requireRole('editor', 'admin', 'superadmin'), listServices);
router.post('/services', requireRole('editor', 'admin', 'superadmin'), validateBody(serviceSchema), createService);
router.patch('/services/:id', requireRole('editor', 'admin', 'superadmin'), validateBody(serviceSchema), updateService);
router.delete('/services/:id', requireRole('admin', 'superadmin'), deleteService);
router.post('/services/:id/publish', requireRole('admin', 'superadmin', 'editor'), publishService);
router.post('/services/:id/schedule', requireRole('admin', 'superadmin', 'editor'), scheduleService);

// Blog posts
router.get('/blogposts', requireRole('editor', 'admin', 'superadmin'), listPosts);
router.post('/blogposts', requireRole('editor', 'admin', 'superadmin'), validateBody(blogSchema), createPost);
router.patch('/blogposts/:id', requireRole('editor', 'admin', 'superadmin'), validateBody(blogSchema), updatePost);
router.delete('/blogposts/:id', requireRole('admin', 'superadmin'), deletePost);
router.post('/blogposts/:id/publish', requireRole('admin', 'superadmin', 'editor'), publishPost);
router.post('/blogposts/:id/schedule', requireRole('admin', 'superadmin', 'editor'), schedulePost);

// Categories, Tags, Media, Banners, Testimonials, Partners (generic handlers)
router.get('/categories', requireRole('editor', 'admin', 'superadmin'), listModel(Models.Category));
router.post('/categories', requireRole('editor', 'admin', 'superadmin'), createModel(Models.Category));
router.patch('/categories/:id', requireRole('editor', 'admin', 'superadmin'), updateModel(Models.Category));
router.delete('/categories/:id', requireRole('admin', 'superadmin'), deleteModel(Models.Category));

router.get('/tags', requireRole('editor', 'admin', 'superadmin'), listModel(Models.Tag));
router.post('/tags', requireRole('editor', 'admin', 'superadmin'), createModel(Models.Tag));
router.patch('/tags/:id', requireRole('editor', 'admin', 'superadmin'), updateModel(Models.Tag));
router.delete('/tags/:id', requireRole('admin', 'superadmin'), deleteModel(Models.Tag));

router.get('/media', requireRole('editor', 'admin', 'superadmin'), listModel(Models.Media));
router.post('/media', requireRole('editor', 'admin', 'superadmin'), createModel(Models.Media));
router.patch('/media/:id', requireRole('editor', 'admin', 'superadmin'), updateModel(Models.Media));
router.delete('/media/:id', requireRole('admin', 'superadmin'), deleteModel(Models.Media));
// Upload is handled by dedicated media route (multipart)
router.post('/media/upload', requireRole('editor', 'admin', 'superadmin'), (req, res, next) => { res.status(400).json({ status: 'error', message: 'Use /api/media/upload (public route) with multipart form-data' }); });

router.get('/banners', requireRole('editor', 'admin', 'superadmin'), listModel(Models.Banner));
router.post('/banners', requireRole('editor', 'admin', 'superadmin'), validateBody(bannerSchema), createModel(Models.Banner));
router.patch('/banners/:id', requireRole('editor', 'admin', 'superadmin'), validateBody(bannerSchema), updateModel(Models.Banner));
router.delete('/banners/:id', requireRole('admin', 'superadmin'), deleteModel(Models.Banner));

router.get('/testimonials', requireRole('editor', 'admin', 'superadmin'), listModel(Models.Testimonial));
router.post('/testimonials', requireRole('editor', 'admin', 'superadmin'), validateBody(testimonialSchema), createModel(Models.Testimonial));
router.patch('/testimonials/:id', requireRole('editor', 'admin', 'superadmin'), validateBody(testimonialSchema), updateModel(Models.Testimonial));
router.delete('/testimonials/:id', requireRole('admin', 'superadmin'), deleteModel(Models.Testimonial));

router.get('/partners', requireRole('editor', 'admin', 'superadmin'), listModel(Models.Partner));
router.post('/partners', requireRole('editor', 'admin', 'superadmin'), createModel(Models.Partner));
router.patch('/partners/:id', requireRole('editor', 'admin', 'superadmin'), updateModel(Models.Partner));
router.delete('/partners/:id', requireRole('admin', 'superadmin'), deleteModel(Models.Partner));

// Forms & Leads
router.get('/forms', requireRole('editor', 'admin', 'superadmin'), listModel(Models.FormConfig));
router.post('/forms', requireRole('editor', 'admin', 'superadmin'), createModel(Models.FormConfig));
router.get('/leads', requireRole('editor', 'admin', 'superadmin'), listModel(Models.Lead));
router.get('/leads/:id', requireRole('editor', 'admin', 'superadmin'), getModel(Models.Lead));
router.patch('/leads/:id', requireRole('editor', 'admin', 'superadmin'), updateModel(Models.Lead));
router.get('/leads/export', requireRole('editor', 'admin', 'superadmin'), (req, res) => { res.redirect('/api/admin/leads/export?format=csv'); });
router.get('/leads/export/csv', requireRole('editor', 'admin', 'superadmin'), async (req, res) => { const { exportLeadsCSV } = await import('../controllers/lead.controller'); return exportLeadsCSV(req, res); });

// Site settings (singleton operations)
router.get('/settings', requireRole('admin', 'superadmin'), getSiteSettings);
router.patch('/settings', requireRole('admin', 'superadmin'), async (req, res) => {
  const updated = await Models.SiteSettings.findOneAndUpdate({}, req.body, { new: true, upsert: true });
  return res.json({ status: 'ok', data: updated });
});

// Users (SuperAdmin only for create/delete, Admin+ for read/update)
router.get('/users', requireRole('admin', 'superadmin'), listUsers);
router.get('/users/:id', requireRole('admin', 'superadmin'), getUser);
router.post('/users', requireRole('superadmin'), createUser);
router.patch('/users/:id', requireRole('admin', 'superadmin'), updateUser);
router.delete('/users/:id', requireRole('superadmin'), deleteUser);

// FAQ, Stats, ProcessSteps, NavItems, Footer (generic CRUD)
router.get('/faqs', requireRole('editor', 'admin', 'superadmin'), listModel(Models.FAQ));
router.post('/faqs', requireRole('editor', 'admin', 'superadmin'), createModel(Models.FAQ));
router.patch('/faqs/:id', requireRole('editor', 'admin', 'superadmin'), updateModel(Models.FAQ));
router.delete('/faqs/:id', requireRole('admin', 'superadmin'), deleteModel(Models.FAQ));

router.get('/stats', requireRole('editor', 'admin', 'superadmin'), listModel(Models.Stat));
router.post('/stats', requireRole('editor', 'admin', 'superadmin'), createModel(Models.Stat));
router.patch('/stats/:id', requireRole('editor', 'admin', 'superadmin'), updateModel(Models.Stat));
router.delete('/stats/:id', requireRole('admin', 'superadmin'), deleteModel(Models.Stat));

router.get('/process-steps', requireRole('editor', 'admin', 'superadmin'), listModel(Models.ProcessStep));
router.post('/process-steps', requireRole('editor', 'admin', 'superadmin'), createModel(Models.ProcessStep));
router.patch('/process-steps/:id', requireRole('editor', 'admin', 'superadmin'), updateModel(Models.ProcessStep));
router.delete('/process-steps/:id', requireRole('admin', 'superadmin'), deleteModel(Models.ProcessStep));

router.get('/nav-items', requireRole('editor', 'admin', 'superadmin'), listModel(Models.NavItem));
router.post('/nav-items', requireRole('editor', 'admin', 'superadmin'), createModel(Models.NavItem));
router.patch('/nav-items/:id', requireRole('editor', 'admin', 'superadmin'), updateModel(Models.NavItem));
router.delete('/nav-items/:id', requireRole('admin', 'superadmin'), deleteModel(Models.NavItem));

router.get('/footer', requireRole('admin', 'superadmin'), async (req, res) => {
  const footer = await Models.Footer.findOne({}).sort({ publishedAt: -1 }).lean();
  return res.json({ status: 'ok', data: footer || {} });
});
router.patch('/footer', requireRole('admin', 'superadmin'), async (req, res) => {
  const updated = await Models.Footer.findOneAndUpdate({}, { ...req.body, published: true, publishedAt: new Date() }, { new: true, upsert: true });
  return res.json({ status: 'ok', data: updated });
});

// WhyUs Features CRUD
router.get('/why-us-features', requireRole('editor', 'admin', 'superadmin'), listModel(Models.WhyUsFeature));
router.post('/why-us-features', requireRole('editor', 'admin', 'superadmin'), createModel(Models.WhyUsFeature));
router.patch('/why-us-features/:id', requireRole('editor', 'admin', 'superadmin'), updateModel(Models.WhyUsFeature));
router.delete('/why-us-features/:id', requireRole('admin', 'superadmin'), deleteModel(Models.WhyUsFeature));

export default router;
