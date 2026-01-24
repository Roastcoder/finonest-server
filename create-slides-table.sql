-- Create slides table for home page carousel
CREATE TABLE IF NOT EXISTS slides (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    subtitle TEXT,
    description TEXT,
    image_url VARCHAR(500),
    button_text VARCHAR(100),
    button_link VARCHAR(255),
    order_position INT DEFAULT 1,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default slides
INSERT INTO slides (title, subtitle, description, image_url, button_text, button_link, order_position, is_active) VALUES
('Your Dream Home', 'with Simpler Faster Friendlier Home Loans', 'Get the best home loan rates with 100% paperless processing', '/assets/hero-home-loan.jpg', 'Check Now', '/services/home-loan', 1, TRUE),
('Your Dream Car', 'with Simpler Faster Friendlier Vehicle Loans', 'Get the lowest vehicle loan rates with 100% paperless processing', '/assets/hero-car-loan.jpg', 'Check Now', '/services/car-loan', 2, TRUE),
('Business Growth', 'with Simpler Faster Friendlier Business Loans', 'Expand your business with quick disbursal within 48 hours', '/assets/hero-business-loan.jpg', 'Check Now', '/services/business-loan', 3, TRUE);