# CMS Content Seeding Guide

This guide explains how to seed the Finonest CMS with initial content.

## Prerequisites

1. **MongoDB must be running**
   - Local: `mongod` or MongoDB service running
   - Or use MongoDB Atlas connection string

2. **Environment Variables**
   - Set `MONGO_URI` in your `.env` file (defaults to `mongodb://localhost:27017/finonest-dev`)
   - Optional: Set `SEED_ADMIN_EMAIL` and `SEED_ADMIN_PASSWORD` for admin user

## Running the Seeding Script

### Option 1: Using npm script
```bash
npm run seed:cms
```

### Option 2: Using ts-node directly
```bash
cd server
npx ts-node scripts/seed-cms.ts
```

### Option 3: Using tsx (if installed)
```bash
cd server
npx tsx scripts/seed-cms.ts
```

## What Gets Seeded

The seeding script creates:

1. **SuperAdmin User**
   - Email: `admin@finonest.com` (or from `SEED_ADMIN_EMAIL`)
   - Password: `Admin@123` (or from `SEED_ADMIN_PASSWORD`)
   - Role: `superadmin`

2. **Stats** (4 items)
   - Customers Annually: 5.8 Lacs+
   - Cities Covered: 150+
   - Branches: 587+
   - Disbursed Annually: 61,000 Cr+

3. **Process Steps** (4 items)
   - Apply Online
   - Document Verification
   - Quick Approval
   - Loan Disbursement

4. **FAQs** (5 items)
   - Eligibility questions
   - Document requirements
   - Process timelines
   - CIBIL score requirements
   - Prepayment options

5. **Navigation Items** (12 items)
   - Header navigation (6 items)
   - Footer navigation (6 items)

6. **Footer Content**
   - Quick links
   - Contact information
   - Social media links
   - Legal links

7. **WhyUs Features** (6 items)
   - Quick Approval
   - Simple Process
   - Best Rates
   - 24/7 Support
   - 50+ Bank Partners
   - 100% Transparent

8. **Services** (5 items)
   - Home Loan
   - Personal Loan
   - Car Loan
   - Business Loan
   - Credit Cards

9. **Categories & Tags**
   - 5 Categories (Loans, Credit Score, Financial Tips, etc.)
   - 5 Tags (Home Loan, Personal Loan, etc.)

10. **Testimonials** (4 items)
    - Customer reviews with ratings

11. **Banking Partners** (6 items)
    - HDFC Bank, ICICI Bank, Axis Bank, SBI, Kotak, PNB

12. **Site Settings**
    - Site name, tagline
    - Contact information
    - Social media links
    - SEO metadata

13. **Blog Posts** (2 sample posts)
    - How to Improve Your Credit Score
    - Home Loan Eligibility Guide

## Environment Variables

Create a `.env` file in the project root:

```env
MONGO_URI=mongodb://localhost:27017/finonest-dev
SEED_ADMIN_EMAIL=admin@finonest.com
SEED_ADMIN_PASSWORD=Admin@123
```

## Troubleshooting

### MongoDB Connection Error
If you see `ECONNREFUSED`:
1. Make sure MongoDB is running: `mongod` or start MongoDB service
2. Check your `MONGO_URI` in `.env`
3. For MongoDB Atlas, use the full connection string

### Duplicate Key Errors
The script uses `upsert: true`, so it will update existing records instead of creating duplicates. Safe to run multiple times.

### TypeScript Errors
Make sure all dependencies are installed:
```bash
npm install
```

## After Seeding

1. **Login to Admin Panel**
   - Go to `/admin/login`
   - Use the seeded admin credentials

2. **Verify Content**
   - Check `/admin/cms` for all seeded content
   - Verify frontend displays CMS content correctly

3. **Customize Content**
   - Edit any seeded content through the admin panel
   - Add more content as needed

## Notes

- The script uses `updateOne` with `upsert: true`, so it's safe to run multiple times
- Existing content will be updated, not duplicated
- All seeded content is marked as `published: true`
- Dates are set to current date/time
