const https = require('https');

// Admin login function
function adminLogin() {
  return new Promise((resolve, reject) => {
    const loginData = JSON.stringify({
      email: 'admin@finonest.com',
      password: 'admin123'
    });

    const options = {
      hostname: 'api.finonest.com',
      path: '/api/auth/login',
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Content-Length': loginData.length
      }
    };

    const req = https.request(options, (res) => {
      let data = '';
      res.on('data', (chunk) => data += chunk);
      res.on('end', () => {
        try {
          const response = JSON.parse(data);
          resolve(response.token);
        } catch (e) {
          reject(e);
        }
      });
    });

    req.on('error', reject);
    req.write(loginData);
    req.end();
  });
}

// Create blog function
function createBlog(token) {
  return new Promise((resolve, reject) => {
    const blogData = JSON.stringify({
      title: "5 Essential Tips to Improve Your Credit Score Fast",
      excerpt: "Learn proven strategies to boost your credit score quickly and unlock better loan opportunities with lower interest rates.",
      content: "Your credit score is one of the most important financial metrics that affects your ability to get loans, credit cards, and even rent an apartment. Here are 5 essential tips to improve your credit score fast:\n\n1. **Pay Your Bills on Time**: Payment history accounts for 35% of your credit score. Set up automatic payments to ensure you never miss a due date.\n\n2. **Keep Credit Utilization Low**: Try to use less than 30% of your available credit limit. Ideally, keep it below 10% for the best results.\n\n3. **Don't Close Old Credit Cards**: Length of credit history matters. Keep your oldest accounts open to maintain a longer average account age.\n\n4. **Monitor Your Credit Report**: Check for errors and dispute any inaccuracies. You can get free credit reports from all three bureaus annually.\n\n5. **Consider a Secured Credit Card**: If you have poor credit, a secured card can help you rebuild your credit history with responsible use.\n\nRemember, improving your credit score takes time, but these strategies can help you see improvements in as little as 30-60 days.",
      category: "Credit Score",
      status: "published",
      image_url: "https://images.unsplash.com/photo-1554224155-6726b3ff858f?w=800&h=400&fit=crop"
    });

    const options = {
      hostname: 'api.finonest.com',
      path: '/api/blogs',
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`,
        'Content-Length': blogData.length
      }
    };

    const req = https.request(options, (res) => {
      let data = '';
      res.on('data', (chunk) => data += chunk);
      res.on('end', () => {
        console.log('Blog creation response:', data);
        resolve(data);
      });
    });

    req.on('error', reject);
    req.write(blogData);
    req.end();
  });
}

// Main execution
async function main() {
  try {
    console.log('Logging in as admin...');
    const token = await adminLogin();
    console.log('Login successful, token received');
    
    console.log('Creating blog post...');
    await createBlog(token);
    console.log('Blog post created successfully!');
  } catch (error) {
    console.error('Error:', error);
  }
}

main();