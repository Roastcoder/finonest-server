#!/bin/bash

# Sample blog creation script using API
API_URL="https://api.finonest.com/api/admin/blogs"

# First get admin token (you'll need to replace with actual admin credentials)
echo "Creating sample blogs via API..."

# Blog 1: Credit Score Tips
curl -X POST "$API_URL" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -d '{
    "title": "5 Essential Tips to Improve Your Credit Score Fast",
    "excerpt": "Learn proven strategies to boost your credit score quickly and unlock better loan opportunities with lower interest rates.",
    "content": "Your credit score is one of the most important financial metrics that affects your ability to get loans, credit cards, and even rent an apartment. Here are 5 essential tips to improve your credit score fast:\n\n1. **Pay Your Bills on Time**: Payment history accounts for 35% of your credit score. Set up automatic payments to ensure you never miss a due date.\n\n2. **Keep Credit Utilization Low**: Try to use less than 30% of your available credit limit. Ideally, keep it below 10% for the best results.\n\n3. **Don'\''t Close Old Credit Cards**: Length of credit history matters. Keep your oldest accounts open to maintain a longer average account age.\n\n4. **Monitor Your Credit Report**: Check for errors and dispute any inaccuracies. You can get free credit reports from all three bureaus annually.\n\n5. **Consider a Secured Credit Card**: If you have poor credit, a secured card can help you rebuild your credit history with responsible use.\n\nRemember, improving your credit score takes time, but these strategies can help you see improvements in as little as 30-60 days.",
    "category": "Credit Score",
    "status": "published",
    "image_url": "https://images.unsplash.com/photo-1554224155-6726b3ff858f?w=800&h=400&fit=crop"
  }'

echo -e "\n\nBlog 1 created"

# Blog 2: Home Loan vs Personal Loan
curl -X POST "$API_URL" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -d '{
    "title": "Home Loan vs Personal Loan: Which is Right for You?",
    "excerpt": "Understand the key differences between home loans and personal loans to make the best financial decision for your needs.",
    "content": "When it comes to borrowing money, choosing between a home loan and personal loan can be confusing. Here'\''s a comprehensive comparison to help you decide:\n\n**Home Loans:**\n- Lower interest rates (typically 7-9%)\n- Longer repayment terms (15-30 years)\n- Secured by property\n- Tax benefits available\n- Higher loan amounts\n- Longer processing time\n\n**Personal Loans:**\n- Higher interest rates (10-20%)\n- Shorter repayment terms (1-5 years)\n- Unsecured (no collateral required)\n- No tax benefits\n- Lower loan amounts\n- Faster processing\n\n**When to Choose a Home Loan:**\n- Buying or constructing a house\n- Home renovation or extension\n- Need large amounts with lower EMIs\n- Want tax benefits\n\n**When to Choose a Personal Loan:**\n- Medical emergencies\n- Wedding expenses\n- Education costs\n- Debt consolidation\n- Need funds quickly\n\nAt Finonest, we help you compare options and choose the loan that best fits your financial situation and goals.",
    "category": "Home Loan",
    "status": "published",
    "image_url": "https://images.unsplash.com/photo-1560518883-ce09059eeffa?w=800&h=400&fit=crop"
  }'

echo -e "\n\nBlog 2 created"

# Blog 3: Car Loan Interest Rates
curl -X POST "$API_URL" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -d '{
    "title": "Car Loan Interest Rates in 2024: What You Need to Know",
    "excerpt": "Get the latest insights on car loan interest rates, factors affecting them, and tips to secure the best deals in 2024.",
    "content": "Car loan interest rates have seen significant changes in 2024. Here'\''s everything you need to know to get the best deal:\n\n**Current Interest Rate Ranges:**\n- New Cars: 7.5% - 12% per annum\n- Used Cars: 9% - 15% per annum\n- Electric Vehicles: 7% - 10% per annum (special rates)\n\n**Factors Affecting Your Interest Rate:**\n\n1. **Credit Score**: Higher scores get better rates\n   - 750+: Best rates available\n   - 650-749: Moderate rates\n   - Below 650: Higher rates\n\n2. **Down Payment**: Larger down payments reduce rates\n   - 20%+ down payment: Lower rates\n   - 10-20%: Standard rates\n   - Less than 10%: Higher rates\n\n3. **Loan Tenure**: \n   - 3-5 years: Lower rates\n   - 5-7 years: Moderate rates\n   - 7+ years: Higher rates\n\n4. **Vehicle Age**: Newer cars get better rates\n\n**Tips to Get the Best Rate:**\n- Compare offers from multiple lenders\n- Negotiate with dealers\n- Consider pre-approved loans\n- Maintain a good credit score\n- Choose shorter loan terms if possible\n\n**Special Offers in 2024:**\n- Festival season discounts\n- Electric vehicle incentives\n- First-time buyer programs\n- Women borrower benefits\n\nContact Finonest to compare car loan offers from top banks and get pre-approved today!",
    "category": "Car Loan",
    "status": "published",
    "image_url": "https://images.unsplash.com/photo-1449824913935-59a10b8d2000?w=800&h=400&fit=crop"
  }'

echo -e "\n\nBlog 3 created"

echo -e "\n\nSample blogs creation completed!"
echo "Note: Replace YOUR_ADMIN_TOKEN with actual admin authentication token"