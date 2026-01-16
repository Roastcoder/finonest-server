<?php
require_once 'config/database.php';

// Sample blog posts data
$sampleBlogs = [
    [
        'title' => '5 Essential Tips to Improve Your Credit Score Fast',
        'excerpt' => 'Learn proven strategies to boost your credit score quickly and unlock better loan opportunities with lower interest rates.',
        'content' => 'Your credit score is one of the most important financial metrics that affects your ability to get loans, credit cards, and even rent an apartment. Here are 5 essential tips to improve your credit score fast:

1. **Pay Your Bills on Time**: Payment history accounts for 35% of your credit score. Set up automatic payments to ensure you never miss a due date.

2. **Keep Credit Utilization Low**: Try to use less than 30% of your available credit limit. Ideally, keep it below 10% for the best results.

3. **Don\'t Close Old Credit Cards**: Length of credit history matters. Keep your oldest accounts open to maintain a longer average account age.

4. **Monitor Your Credit Report**: Check for errors and dispute any inaccuracies. You can get free credit reports from all three bureaus annually.

5. **Consider a Secured Credit Card**: If you have poor credit, a secured card can help you rebuild your credit history with responsible use.

Remember, improving your credit score takes time, but these strategies can help you see improvements in as little as 30-60 days.',
        'category' => 'Credit Score',
        'author' => 'Finonest Team',
        'status' => 'published',
        'image_url' => 'https://images.unsplash.com/photo-1554224155-6726b3ff858f?w=800&h=400&fit=crop',
        'video_url' => ''
    ],
    [
        'title' => 'Home Loan vs Personal Loan: Which is Right for You?',
        'excerpt' => 'Understand the key differences between home loans and personal loans to make the best financial decision for your needs.',
        'content' => 'When it comes to borrowing money, choosing between a home loan and personal loan can be confusing. Here\'s a comprehensive comparison to help you decide:

**Home Loans:**
- Lower interest rates (typically 7-9%)
- Longer repayment terms (15-30 years)
- Secured by property
- Tax benefits available
- Higher loan amounts
- Longer processing time

**Personal Loans:**
- Higher interest rates (10-20%)
- Shorter repayment terms (1-5 years)
- Unsecured (no collateral required)
- No tax benefits
- Lower loan amounts
- Faster processing

**When to Choose a Home Loan:**
- Buying or constructing a house
- Home renovation or extension
- Need large amounts with lower EMIs
- Want tax benefits

**When to Choose a Personal Loan:**
- Medical emergencies
- Wedding expenses
- Education costs
- Debt consolidation
- Need funds quickly

At Finonest, we help you compare options and choose the loan that best fits your financial situation and goals.',
        'category' => 'Home Loan',
        'author' => 'Finonest Team',
        'status' => 'published',
        'image_url' => 'https://images.unsplash.com/photo-1560518883-ce09059eeffa?w=800&h=400&fit=crop',
        'video_url' => ''
    ],
    [
        'title' => 'Car Loan Interest Rates in 2024: What You Need to Know',
        'excerpt' => 'Get the latest insights on car loan interest rates, factors affecting them, and tips to secure the best deals in 2024.',
        'content' => 'Car loan interest rates have seen significant changes in 2024. Here\'s everything you need to know to get the best deal:

**Current Interest Rate Ranges:**
- New Cars: 7.5% - 12% per annum
- Used Cars: 9% - 15% per annum
- Electric Vehicles: 7% - 10% per annum (special rates)

**Factors Affecting Your Interest Rate:**

1. **Credit Score**: Higher scores get better rates
   - 750+: Best rates available
   - 650-749: Moderate rates
   - Below 650: Higher rates

2. **Down Payment**: Larger down payments reduce rates
   - 20%+ down payment: Lower rates
   - 10-20%: Standard rates
   - Less than 10%: Higher rates

3. **Loan Tenure**: 
   - 3-5 years: Lower rates
   - 5-7 years: Moderate rates
   - 7+ years: Higher rates

4. **Vehicle Age**: Newer cars get better rates

**Tips to Get the Best Rate:**
- Compare offers from multiple lenders
- Negotiate with dealers
- Consider pre-approved loans
- Maintain a good credit score
- Choose shorter loan terms if possible

**Special Offers in 2024:**
- Festival season discounts
- Electric vehicle incentives
- First-time buyer programs
- Women borrower benefits

Contact Finonest to compare car loan offers from top banks and get pre-approved today!',
        'category' => 'Car Loan',
        'author' => 'Finonest Team',
        'status' => 'published',
        'image_url' => 'https://images.unsplash.com/photo-1449824913935-59a10b8d2000?w=800&h=400&fit=crop',
        'video_url' => ''
    ],
    [
        'title' => 'Business Loan Documentation: Complete Checklist for 2024',
        'excerpt' => 'Ensure your business loan application is approved quickly with our comprehensive documentation checklist and expert tips.',
        'content' => 'Getting a business loan approved requires proper documentation. Here\'s your complete checklist for 2024:

**For Proprietorship/Partnership:**

*Identity & Address Proof:*
- PAN Card of proprietor/partners
- Aadhaar Card
- Passport/Voter ID/Driving License
- Utility bills (electricity/telephone)

*Business Documents:*
- Business registration certificate
- Shop & Establishment license
- GST registration certificate
- Trade license (if applicable)
- Partnership deed (for partnerships)

*Financial Documents:*
- Last 2 years ITR with computation
- Last 2 years audited financials
- Bank statements (12-24 months)
- GST returns (last 12 months)
- Current account statements

**For Private Limited Companies:**

*Additional Documents:*
- Certificate of Incorporation
- Memorandum & Articles of Association
- Board resolution for loan
- List of directors with KYC
- Share certificates

**Common Requirements:**

*Business Proof:*
- Business vintage proof (2+ years)
- Office lease agreement
- Electricity bills in business name
- Business photographs

*Financial Health:*
- Profit & Loss statements
- Balance sheets
- Cash flow projections
- Existing loan statements
- Credit bureau reports

**Pro Tips for Faster Approval:**
1. Keep all documents updated and certified
2. Maintain clean banking records
3. Ensure GST compliance
4. Prepare realistic business projections
5. Have collateral documents ready

**Digital Documentation:**
Many lenders now accept digital documents:
- Scanned copies in PDF format
- Digital signatures accepted
- Online verification processes
- Faster processing times

At Finonest, we help you prepare and organize all required documents for a smooth loan application process. Our experts review your documentation before submission to ensure maximum approval chances.',
        'category' => 'Business Loan',
        'author' => 'Finonest Team',
        'status' => 'published',
        'image_url' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=800&h=400&fit=crop',
        'video_url' => ''
    ],
    [
        'title' => 'Personal Loan EMI Calculator: Plan Your Finances Better',
        'excerpt' => 'Use our comprehensive guide to understand personal loan EMIs and make informed borrowing decisions with smart financial planning.',
        'content' => 'Understanding your personal loan EMI is crucial for financial planning. Here\'s everything you need to know:

**What is EMI?**
EMI (Equated Monthly Installment) is the fixed amount you pay monthly to repay your loan, including both principal and interest.

**EMI Calculation Formula:**
EMI = [P × R × (1+R)^N] / [(1+R)^N-1]

Where:
- P = Principal loan amount
- R = Monthly interest rate (Annual rate ÷ 12)
- N = Number of monthly installments

**Example Calculation:**
Loan Amount: ₹5,00,000
Interest Rate: 12% per annum
Tenure: 3 years (36 months)

Monthly Interest Rate = 12% ÷ 12 = 1% = 0.01
EMI = [5,00,000 × 0.01 × (1.01)^36] / [(1.01)^36-1]
EMI = ₹16,607 (approximately)

**Factors Affecting Your EMI:**

1. **Loan Amount**: Higher amount = Higher EMI
2. **Interest Rate**: Higher rate = Higher EMI
3. **Tenure**: Longer tenure = Lower EMI (but higher total interest)

**EMI vs Tenure Comparison:**
For ₹5 Lakh at 12% interest:
- 2 years: EMI ₹23,536 | Total Interest ₹64,864
- 3 years: EMI ₹16,607 | Total Interest ₹97,852
- 5 years: EMI ₹11,122 | Total Interest ₹1,67,320

**Tips to Reduce EMI Burden:**

1. **Choose Longer Tenure**: Lower monthly outflow
2. **Make Prepayments**: Reduce principal faster
3. **Negotiate Interest Rates**: Compare multiple lenders
4. **Maintain Good Credit Score**: Get better rates
5. **Consider Step-up EMIs**: Start low, increase gradually

**EMI Planning Tips:**
- Keep EMI below 40% of monthly income
- Factor in other monthly expenses
- Keep emergency funds separate
- Consider future income changes
- Plan for prepayment opportunities

**Online EMI Calculators:**
Use digital tools to:
- Compare different loan scenarios
- Plan optimal tenure
- Calculate total interest payable
- Understand amortization schedule

**Smart EMI Strategies:**
1. **Bi-weekly Payments**: Pay half EMI every 2 weeks
2. **Annual Prepayments**: Use bonuses/increments
3. **Rate Reduction**: Switch lenders if rates drop
4. **Partial Prepayments**: Reduce tenure or EMI

At Finonest, we provide personalized EMI calculations and help you choose the most suitable loan structure for your financial goals.',
        'category' => 'Personal Loan',
        'author' => 'Finonest Team',
        'status' => 'published',
        'image_url' => 'https://images.unsplash.com/photo-1554224154-26032fced8bd?w=800&h=400&fit=crop',
        'video_url' => ''
    ],
    [
        'title' => 'Financial Planning for Millennials: A Complete Guide',
        'excerpt' => 'Master your finances with this comprehensive guide tailored for millennials, covering investments, loans, and wealth building strategies.',
        'content' => 'Financial planning for millennials requires a different approach than previous generations. Here\'s your complete roadmap:

**Phase 1: Foundation Building (Age 22-28)**

*Emergency Fund:*
- Build 6-12 months of expenses
- Keep in liquid savings account
- Start with ₹1,000/month SIP

*Insurance:*
- Term life insurance (10-15x annual income)
- Health insurance (₹5-10 lakhs)
- Avoid investment-linked policies initially

*Debt Management:*
- Pay off high-interest debt first
- Use personal loans for consolidation
- Maintain credit score above 750

**Phase 2: Growth & Accumulation (Age 28-35)**

*Investment Strategy:*
- 70% equity, 30% debt allocation
- Start SIPs in diversified mutual funds
- Consider ELSS for tax savings
- Begin real estate planning

*Career & Income:*
- Focus on skill development
- Negotiate salary increments
- Consider side income sources
- Build professional network

*Major Purchases:*
- Plan home loan strategically
- Choose car loans wisely
- Avoid lifestyle inflation

**Phase 3: Wealth Optimization (Age 35+)**

*Advanced Planning:*
- Diversify investment portfolio
- Consider international funds
- Plan children\'s education
- Retirement corpus building

**Millennial-Specific Challenges:**

1. **Gig Economy**: Irregular income planning
2. **Delayed Milestones**: Later marriage/home buying
3. **Technology**: Digital-first financial products
4. **Inflation**: Rising costs of living
5. **Longevity**: Longer retirement planning needed

**Smart Financial Habits:**

*Budgeting:*
- Use 50-30-20 rule
- Track expenses digitally
- Automate savings
- Review monthly

*Investment Approach:*
- Start early for compounding
- Use technology platforms
- Diversify across asset classes
- Stay disciplined during volatility

*Loan Strategy:*
- Compare rates online
- Use pre-approved offers
- Maintain good credit history
- Consider balance transfers

**Digital Tools for Millennials:**
- Investment apps for SIPs
- Expense tracking applications
- Credit score monitoring
- Loan comparison platforms
- Tax planning software

**Common Mistakes to Avoid:**
1. Delaying investment start
2. Over-spending on lifestyle
3. Ignoring insurance needs
4. Taking unnecessary loans
5. Not building emergency funds

**Millennial Investment Options:**
- Mutual Fund SIPs
- Direct equity (for experienced)
- PPF for tax savings
- NPS for retirement
- Real estate (after 30)
- Gold ETFs (5-10% allocation)

**Tax Planning:**
- Maximize 80C deductions
- Use ELSS mutual funds
- Plan HRA/LTA benefits
- Consider NPS additional deduction
- Optimize capital gains

At Finonest, we understand millennial financial needs and provide customized solutions for loans, investments, and financial planning.',
        'category' => 'Financial Planning',
        'author' => 'Finonest Team',
        'status' => 'draft',
        'image_url' => 'https://images.unsplash.com/photo-1559526324-4b87b5e36e44?w=800&h=400&fit=crop',
        'video_url' => ''
    ]
];

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Create blogs table if it doesn't exist
    $createTable = "
    CREATE TABLE IF NOT EXISTS blogs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        excerpt TEXT NOT NULL,
        content LONGTEXT NOT NULL,
        category VARCHAR(100) NOT NULL,
        author VARCHAR(100) NOT NULL DEFAULT 'Finonest Team',
        status ENUM('draft', 'published') DEFAULT 'draft',
        image_url VARCHAR(500),
        video_url VARCHAR(500),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    $pdo->exec($createTable);
    
    // Insert sample blogs
    $stmt = $pdo->prepare("
        INSERT INTO blogs (title, excerpt, content, category, author, status, image_url, video_url) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    foreach ($sampleBlogs as $blog) {
        $stmt->execute([
            $blog['title'],
            $blog['excerpt'],
            $blog['content'],
            $blog['category'],
            $blog['author'],
            $blog['status'],
            $blog['image_url'],
            $blog['video_url']
        ]);
    }
    
    echo "Sample blogs created successfully!\n";
    echo "Created " . count($sampleBlogs) . " blog posts.\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>