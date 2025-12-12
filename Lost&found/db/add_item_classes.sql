-- =====================================================
-- Add School-Appropriate Item Class Categories
-- =====================================================
-- This script adds comprehensive item class categories
-- suitable for a university lost & found system
-- =====================================================

-- Insert new item classes (using INSERT IGNORE to avoid duplicates)
INSERT IGNORE INTO `itemclass` (`ClassName`, `Description`) VALUES
('Electronics', 'Electronic devices like phones, laptops, tablets, chargers, headphones, etc.'),
('Bags', 'Backpacks, purses, wallets, handbags, tote bags, etc.'),
('Books & Notebooks', 'Textbooks, notebooks, binders, planners, study materials'),
('Clothing & Accessories', 'Jackets, sweaters, hats, scarves, gloves, belts, etc.'),
('ID Cards & Documents', 'Student IDs, driver licenses, certificates, important papers'),
('Keys & Keychains', 'House keys, car keys, keychains, lanyards'),
('Stationery & School Supplies', 'Pens, pencils, calculators, rulers, erasers, highlighters'),
('Jewelry & Watches', 'Rings, necklaces, bracelets, watches, earrings'),
('Sports Equipment', 'Balls, rackets, gym bags, sports gear'),
('Umbrellas', 'Umbrellas and rain gear'),
('Water Bottles & Containers', 'Water bottles, lunch boxes, containers, thermos'),
('Eyewear', 'Glasses, sunglasses, contact lens cases'),
('Others', 'Items that do not fit into other categories');

-- Note: If you want to update existing categories with descriptions:
-- UPDATE `itemclass` SET `Description` = 'Electronic devices like phones, laptops, tablets, chargers, headphones, etc.' WHERE `ClassName` = 'Electronics';
-- UPDATE `itemclass` SET `Description` = 'Backpacks, purses, wallets, handbags, tote bags, etc.' WHERE `ClassName` = 'Bags';

