-- ============================================================
-- ShopNest — Multi-Vendor E-Commerce Website (PHP + MySQL)
-- Database: `ecomdb`
-- Import with phpMyAdmin (Import tab) or:  mysql -u root < ecomdb.sql
--
-- Demo logins (change after first login):
--   Admin    : admin              / admin@123
--   Seller   : ravi@shopnest.com   / seller@123
--   Customer : rahul@gmail.com    / user@123
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+05:30";
SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS `ecomdb` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `ecomdb`;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `tblreview`, `tblwishlist`, `tblorderitems`, `tblorders`, `tblproduct`,
                     `tblcategory`, `tblcustomer`, `tblseller`, `tbladmin`, `tblpage`, `tblenquiry`;
SET FOREIGN_KEY_CHECKS = 1;

-- --------------------------------------------------------
-- Table structure for table `tbladmin`
-- --------------------------------------------------------
CREATE TABLE `tbladmin` (
  `ID` int(10) NOT NULL AUTO_INCREMENT,
  `AdminName` varchar(120) DEFAULT NULL,
  `UserName` varchar(120) NOT NULL,
  `MobileNumber` bigint(10) DEFAULT NULL,
  `Email` varchar(120) DEFAULT NULL,
  `Password` varchar(255) NOT NULL,
  `AdminRegdate` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`ID`),
  UNIQUE KEY `uq_admin_username` (`UserName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `tbladmin` (`ID`, `AdminName`, `UserName`, `MobileNumber`, `Email`, `Password`) VALUES
(1, 'Admin', 'admin', 7898799720, 'admin@shopnest.com', '$2y$10$G5xuFmCOi4tvlkyQtDxZX.0lLAb1ZRgC4G1EA0zFePbjSYTxeOVo.');

-- --------------------------------------------------------
-- Table structure for table `tblseller`  (like tblcompany in CRMS)
-- --------------------------------------------------------
CREATE TABLE `tblseller` (
  `ID` int(10) NOT NULL AUTO_INCREMENT,
  `SellerName` varchar(120) NOT NULL,
  `ShopName` varchar(150) NOT NULL,
  `Email` varchar(120) NOT NULL,
  `MobileNumber` bigint(10) DEFAULT NULL,
  `Address` mediumtext DEFAULT NULL,
  `GSTNumber` varchar(30) DEFAULT NULL,
  `Logo` varchar(150) DEFAULT NULL,
  `Password` varchar(255) NOT NULL,
  `Status` enum('Pending','Active','Blocked') NOT NULL DEFAULT 'Pending',
  `RegDate` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`ID`),
  UNIQUE KEY `uq_seller_email` (`Email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `tblseller` (`ID`, `SellerName`, `ShopName`, `Email`, `MobileNumber`, `Address`, `GSTNumber`, `Password`, `Status`, `RegDate`) VALUES
(1, 'Ravi Sharma', 'Ravi Electronics', 'ravi@shopnest.com', 9876543210, 'Shop 12, Nehru Place, New Delhi', '07AAAAA0000A1Z5', '$2y$10$qy6fgZnsFVZUULRhsBorV.CRoiU4Mcdrq2IW/lpH.kvhJ9qoL8RGS', 'Active', '2026-09-01 10:15:00'),
(2, 'Meera Iyer', 'Meera Fashion House', 'meera@shopnest.com', 9123456780, '45 Commercial Street, Bengaluru', '29BBBBB1111B1Z6', '$2y$10$qy6fgZnsFVZUULRhsBorV.CRoiU4Mcdrq2IW/lpH.kvhJ9qoL8RGS', 'Active', '2026-09-05 12:40:00'),
(3, 'Arjun Patel', 'Patel Home Store', 'arjun@shopnest.com', 9988776655, 'CG Road, Ahmedabad', '24CCCCC2222C1Z7', '$2y$10$qy6fgZnsFVZUULRhsBorV.CRoiU4Mcdrq2IW/lpH.kvhJ9qoL8RGS', 'Pending', '2026-11-18 09:05:00');

-- --------------------------------------------------------
-- Table structure for table `tblcustomer`  (like tbluser in CRMS)
-- --------------------------------------------------------
CREATE TABLE `tblcustomer` (
  `ID` int(10) NOT NULL AUTO_INCREMENT,
  `FullName` varchar(120) NOT NULL,
  `Email` varchar(120) NOT NULL,
  `MobileNumber` bigint(10) DEFAULT NULL,
  `Gender` varchar(20) DEFAULT NULL,
  `Address` mediumtext DEFAULT NULL,
  `City` varchar(80) DEFAULT NULL,
  `State` varchar(80) DEFAULT NULL,
  `Pincode` varchar(10) DEFAULT NULL,
  `Image` varchar(150) DEFAULT NULL,
  `Password` varchar(255) NOT NULL,
  `RegDate` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`ID`),
  UNIQUE KEY `uq_customer_email` (`Email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `tblcustomer` (`ID`, `FullName`, `Email`, `MobileNumber`, `Gender`, `Address`, `City`, `State`, `Pincode`, `Password`, `RegDate`) VALUES
(1, 'Rahul Saxena', 'rahul@gmail.com', 8989898989, 'Male', 'H-456 Mayur Vihar Phase 1', 'New Delhi', 'Delhi', '110091', '$2y$10$NJIjzTC1CEjuBtJ4OekZKej19MYymQYJG3htBfImeoE1M6nSU2cha', '2026-09-10 08:00:00'),
(2, 'Farha Akthar', 'farha@gmail.com', 2525252525, 'Female', '22 MG Road', 'Pune', 'Maharashtra', '411001', '$2y$10$NJIjzTC1CEjuBtJ4OekZKej19MYymQYJG3htBfImeoE1M6nSU2cha', '2026-09-22 11:30:00'),
(3, 'Akash Jain', 'jain@gmail.com', 6544646544, 'Male', '7 Lake Town', 'Kolkata', 'West Bengal', '700089', '$2y$10$NJIjzTC1CEjuBtJ4OekZKej19MYymQYJG3htBfImeoE1M6nSU2cha', '2026-10-03 16:45:00'),
(4, 'Ginni Mishra', 'ginni@gmail.com', 3636363663, 'Female', '19 Civil Lines', 'Jaipur', 'Rajasthan', '302006', '$2y$10$NJIjzTC1CEjuBtJ4OekZKej19MYymQYJG3htBfImeoE1M6nSU2cha', '2026-10-28 19:20:00');

-- --------------------------------------------------------
-- Table structure for table `tblcategory`
-- --------------------------------------------------------
CREATE TABLE `tblcategory` (
  `ID` int(10) NOT NULL AUTO_INCREMENT,
  `CategoryName` varchar(100) NOT NULL,
  `Description` varchar(255) DEFAULT NULL,
  `Icon` varchar(50) DEFAULT 'fa-tag',
  `CreationDate` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`ID`),
  UNIQUE KEY `uq_category_name` (`CategoryName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `tblcategory` (`ID`, `CategoryName`, `Description`, `Icon`) VALUES
(1, 'Mobiles', 'Smartphones and accessories', 'fa-mobile-screen'),
(2, 'Laptops', 'Laptops, notebooks and peripherals', 'fa-laptop'),
(3, 'Fashion', 'Clothing for men and women', 'fa-shirt'),
(4, 'Footwear', 'Shoes, sneakers and sandals', 'fa-shoe-prints'),
(5, 'Home & Kitchen', 'Appliances and home essentials', 'fa-blender'),
(6, 'Audio', 'Headphones, earbuds and speakers', 'fa-headphones');

-- --------------------------------------------------------
-- Table structure for table `tblproduct`  (like tblvacancy in CRMS)
-- --------------------------------------------------------
CREATE TABLE `tblproduct` (
  `ID` int(10) NOT NULL AUTO_INCREMENT,
  `SellerID` int(10) NOT NULL,
  `CategoryID` int(10) NOT NULL,
  `ProductName` varchar(200) NOT NULL,
  `Brand` varchar(100) DEFAULT NULL,
  `Description` mediumtext DEFAULT NULL,
  `Price` decimal(10,2) NOT NULL,
  `MRP` decimal(10,2) NOT NULL,
  `Stock` int(10) NOT NULL DEFAULT 0,
  `Image` varchar(150) DEFAULT NULL,
  `Status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `IsFeatured` tinyint(1) NOT NULL DEFAULT 0,
  `CreationDate` timestamp NULL DEFAULT current_timestamp(),
  `UpdationDate` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`ID`),
  KEY `idx_product_seller` (`SellerID`),
  KEY `idx_product_category` (`CategoryID`),
  CONSTRAINT `fk_product_seller` FOREIGN KEY (`SellerID`) REFERENCES `tblseller` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `fk_product_category` FOREIGN KEY (`CategoryID`) REFERENCES `tblcategory` (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `tblproduct` (`ID`, `SellerID`, `CategoryID`, `ProductName`, `Brand`, `Description`, `Price`, `MRP`, `Stock`, `Image`, `IsFeatured`, `CreationDate`) VALUES
(1, 1, 1, 'Galaxy Nova 5G (8GB / 128GB)', 'Samsung', '6.6\" AMOLED display, 50MP triple camera, 5000mAh battery with 25W fast charging. Dual SIM 5G.', 18999.00, 24999.00, 25, 'p1.svg', 1, '2026-09-02 10:00:00'),
(2, 1, 1, 'Redmi Note Pro (6GB / 128GB)', 'Xiaomi', '120Hz display, 108MP camera, 67W turbo charging and stereo speakers.', 15499.00, 19999.00, 40, 'p2.svg', 0, '2026-09-02 10:05:00'),
(3, 1, 1, 'iPhone 15 (128GB)', 'Apple', 'A16 Bionic chip, 48MP main camera, Dynamic Island and USB-C.', 69900.00, 79900.00, 8, 'p3.svg', 1, '2026-09-03 09:00:00'),
(4, 1, 2, 'Inspiron 15 Laptop (i5, 16GB, 512GB SSD)', 'Dell', '15.6\" FHD display, 12th Gen Intel Core i5, Windows 11, backlit keyboard.', 56990.00, 68990.00, 12, 'p4.svg', 1, '2026-09-04 11:00:00'),
(5, 1, 2, 'IdeaPad Slim 3 (Ryzen 5, 8GB, 512GB)', 'Lenovo', 'Thin and light 14\" laptop with all-day battery life.', 42490.00, 54990.00, 15, 'p5.svg', 0, '2026-09-04 11:10:00'),
(6, 1, 2, 'Wireless Keyboard & Mouse Combo', 'Logitech', 'Quiet keys, 2.4GHz wireless receiver, 3-year battery life.', 1795.00, 2495.00, 60, 'p6.svg', 0, '2026-09-05 08:30:00'),
(7, 1, 6, 'Rockerz Wireless Headphones', 'boAt', '40mm drivers, 50-hour playback, ENx noise cancellation for calls.', 1499.00, 3990.00, 80, 'p7.svg', 1, '2026-09-06 14:00:00'),
(8, 1, 6, 'Airdopes True Wireless Earbuds', 'boAt', 'IPX4 water resistant, 42-hour total playback, low-latency gaming mode.', 1099.00, 2990.00, 100, 'p8.svg', 0, '2026-09-06 14:10:00'),
(9, 1, 6, 'Portable Bluetooth Speaker 20W', 'JBL', 'Deep bass, IP67 waterproof, 12 hours playtime.', 3999.00, 5999.00, 30, 'p9.svg', 0, '2026-09-07 09:00:00'),
(10, 2, 3, 'Men\'s Cotton Casual Shirt', 'Peter England', '100% cotton slim-fit shirt, ideal for office and casual wear.', 899.00, 1799.00, 50, 'p10.svg', 1, '2026-09-08 10:00:00'),
(11, 2, 3, 'Women\'s Printed Kurta', 'Biba', 'Rayon straight kurta with ethnic print, three-quarter sleeves.', 1199.00, 2499.00, 45, 'p11.svg', 1, '2026-09-08 10:20:00'),
(12, 2, 3, 'Men\'s Slim Fit Jeans', 'Levi\'s', 'Stretchable denim, mid-rise, classic 5-pocket styling.', 1899.00, 3299.00, 35, 'p12.svg', 0, '2026-09-09 12:00:00'),
(13, 2, 4, 'Running Sports Shoes', 'Puma', 'Lightweight mesh upper with cushioned sole for daily runs.', 2499.00, 4999.00, 40, 'p13.svg', 1, '2026-09-10 15:00:00'),
(14, 2, 4, 'Women\'s Casual Sneakers', 'Bata', 'Comfortable white sneakers for everyday wear.', 1299.00, 1999.00, 30, 'p14.svg', 0, '2026-09-10 15:15:00'),
(15, 2, 4, 'Men\'s Leather Formal Shoes', 'Red Tape', 'Genuine leather derby shoes with padded insole.', 2199.00, 3999.00, 3, 'p15.svg', 0, '2026-09-11 10:00:00'),
(16, 1, 5, 'Mixer Grinder 750W (3 Jars)', 'Prestige', 'Powerful 750W motor, stainless-steel jars, 2-year warranty.', 3299.00, 4995.00, 20, 'p16.svg', 1, '2026-09-12 09:00:00'),
(17, 1, 5, 'Electric Kettle 1.5L', 'Philips', 'Auto shut-off, stainless-steel body, boils in under 5 minutes.', 1149.00, 1695.00, 55, 'p17.svg', 0, '2026-09-12 09:20:00'),
(18, 1, 5, 'Non-Stick Cookware Set (3 pcs)', 'Hawkins', 'Induction compatible fry pan, kadai and tawa.', 2399.00, 3600.00, 0, 'p18.svg', 0, '2026-09-13 11:00:00');

-- --------------------------------------------------------
-- Table structure for table `tblorders`
-- --------------------------------------------------------
CREATE TABLE `tblorders` (
  `ID` int(10) NOT NULL AUTO_INCREMENT,
  `OrderNumber` varchar(20) NOT NULL,
  `CustomerID` int(10) NOT NULL,
  `ShipName` varchar(120) NOT NULL,
  `ShipMobile` varchar(15) NOT NULL,
  `ShipAddress` mediumtext NOT NULL,
  `ShipCity` varchar(80) NOT NULL,
  `ShipState` varchar(80) NOT NULL,
  `ShipPincode` varchar(10) NOT NULL,
  `PaymentMethod` enum('COD','Online') NOT NULL DEFAULT 'COD',
  `PaymentStatus` enum('Pending','Paid','Refunded') NOT NULL DEFAULT 'Pending',
  `SubTotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `ShippingCharge` decimal(10,2) NOT NULL DEFAULT 0.00,
  `TotalAmount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `Notes` varchar(255) DEFAULT NULL,
  `OrderDate` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`ID`),
  UNIQUE KEY `uq_order_number` (`OrderNumber`),
  KEY `idx_order_customer` (`CustomerID`),
  CONSTRAINT `fk_order_customer` FOREIGN KEY (`CustomerID`) REFERENCES `tblcustomer` (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `tblorders` (`ID`, `OrderNumber`, `CustomerID`, `ShipName`, `ShipMobile`, `ShipAddress`, `ShipCity`, `ShipState`, `ShipPincode`, `PaymentMethod`, `PaymentStatus`, `SubTotal`, `ShippingCharge`, `TotalAmount`, `OrderDate`) VALUES
(1, 'SN20261015001', 1, 'Rahul Saxena', '8989898989', 'H-456 Mayur Vihar Phase 1', 'New Delhi', 'Delhi', '110091', 'Online', 'Paid', 20498.00, 0.00, 20498.00, '2026-10-15 13:20:00'),
(2, 'SN20261102002', 2, 'Farha Akthar', '2525252525', '22 MG Road', 'Pune', 'Maharashtra', '411001', 'COD', 'Pending', 3698.00, 0.00, 3698.00, '2026-11-02 18:05:00'),
(3, 'SN20261116003', 1, 'Rahul Saxena', '8989898989', 'H-456 Mayur Vihar Phase 1', 'New Delhi', 'Delhi', '110091', 'COD', 'Pending', 899.00, 49.00, 948.00, '2026-11-16 10:45:00'),
(4, 'SN20261119004', 3, 'Akash Jain', '6544646544', '7 Lake Town', 'Kolkata', 'West Bengal', '700089', 'Online', 'Paid', 56990.00, 0.00, 56990.00, '2026-11-19 21:10:00');

-- --------------------------------------------------------
-- Table structure for table `tblorderitems`
-- (each item carries its own status so every seller manages their own items)
-- --------------------------------------------------------
CREATE TABLE `tblorderitems` (
  `ID` int(10) NOT NULL AUTO_INCREMENT,
  `OrderID` int(10) NOT NULL,
  `ProductID` int(10) DEFAULT NULL,
  `SellerID` int(10) NOT NULL,
  `ProductName` varchar(200) NOT NULL,
  `Price` decimal(10,2) NOT NULL,
  `Quantity` int(5) NOT NULL DEFAULT 1,
  `Status` enum('Pending','Confirmed','Shipped','Delivered','Cancelled') NOT NULL DEFAULT 'Pending',
  `Remark` varchar(255) DEFAULT NULL,
  `UpdationDate` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`ID`),
  KEY `idx_item_order` (`OrderID`),
  KEY `idx_item_seller` (`SellerID`),
  KEY `idx_item_product` (`ProductID`),
  CONSTRAINT `fk_item_order` FOREIGN KEY (`OrderID`) REFERENCES `tblorders` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `fk_item_product` FOREIGN KEY (`ProductID`) REFERENCES `tblproduct` (`ID`) ON DELETE SET NULL,
  CONSTRAINT `fk_item_seller` FOREIGN KEY (`SellerID`) REFERENCES `tblseller` (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `tblorderitems` (`ID`, `OrderID`, `ProductID`, `SellerID`, `ProductName`, `Price`, `Quantity`, `Status`, `Remark`) VALUES
(1, 1, 1, 1, 'Galaxy Nova 5G (8GB / 128GB)', 18999.00, 1, 'Delivered', 'Delivered to customer'),
(2, 1, 7, 1, 'Rockerz Wireless Headphones', 1499.00, 1, 'Delivered', 'Delivered to customer'),
(3, 2, 13, 2, 'Running Sports Shoes', 2499.00, 1, 'Shipped', 'Shipped via BlueDart'),
(4, 2, 11, 2, 'Women\'s Printed Kurta', 1199.00, 1, 'Confirmed', NULL),
(5, 3, 10, 2, 'Men\'s Cotton Casual Shirt', 899.00, 1, 'Pending', NULL),
(6, 4, 4, 1, 'Inspiron 15 Laptop (i5, 16GB, 512GB SSD)', 56990.00, 1, 'Pending', NULL);

-- --------------------------------------------------------
-- Table structure for table `tblwishlist`
-- --------------------------------------------------------
CREATE TABLE `tblwishlist` (
  `ID` int(10) NOT NULL AUTO_INCREMENT,
  `CustomerID` int(10) NOT NULL,
  `ProductID` int(10) NOT NULL,
  `AddedDate` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`ID`),
  UNIQUE KEY `uq_wishlist` (`CustomerID`, `ProductID`),
  CONSTRAINT `fk_wish_customer` FOREIGN KEY (`CustomerID`) REFERENCES `tblcustomer` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `fk_wish_product` FOREIGN KEY (`ProductID`) REFERENCES `tblproduct` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `tblwishlist` (`CustomerID`, `ProductID`) VALUES (1, 3), (1, 13), (2, 7);

-- --------------------------------------------------------
-- Table structure for table `tblreview`
-- --------------------------------------------------------
CREATE TABLE `tblreview` (
  `ID` int(10) NOT NULL AUTO_INCREMENT,
  `ProductID` int(10) NOT NULL,
  `CustomerID` int(10) NOT NULL,
  `Rating` tinyint(1) NOT NULL,
  `Comment` mediumtext DEFAULT NULL,
  `ReviewDate` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`ID`),
  UNIQUE KEY `uq_review` (`ProductID`, `CustomerID`),
  CONSTRAINT `fk_review_product` FOREIGN KEY (`ProductID`) REFERENCES `tblproduct` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `fk_review_customer` FOREIGN KEY (`CustomerID`) REFERENCES `tblcustomer` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `tblreview` (`ProductID`, `CustomerID`, `Rating`, `Comment`, `ReviewDate`) VALUES
(1, 1, 5, 'Excellent phone for the price. Battery easily lasts a full day.', '2026-10-22 09:00:00'),
(7, 1, 4, 'Great bass and very comfortable. Mic could be better.', '2026-10-22 09:05:00');

-- --------------------------------------------------------
-- Table structure for table `tblpage`  (same idea as CRMS)
-- --------------------------------------------------------
CREATE TABLE `tblpage` (
  `ID` int(10) NOT NULL AUTO_INCREMENT,
  `PageType` varchar(50) NOT NULL,
  `PageTitle` varchar(200) DEFAULT NULL,
  `PageDescription` mediumtext DEFAULT NULL,
  `Email` varchar(120) DEFAULT NULL,
  `MobileNumber` bigint(10) DEFAULT NULL,
  `UpdationDate` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`ID`),
  UNIQUE KEY `uq_page_type` (`PageType`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `tblpage` (`PageType`, `PageTitle`, `PageDescription`, `Email`, `MobileNumber`) VALUES
('aboutus', 'About ShopNest', 'ShopNest is a multi-vendor marketplace that connects trusted local sellers with customers across India. Sellers list their products, our admin team verifies them, and customers enjoy secure checkout, cash on delivery and easy order tracking.', NULL, NULL),
('contactus', 'Contact Us', 'ShopNest Pvt. Ltd., 3rd Floor, Tech Park, Sector 62, Noida, Uttar Pradesh 201309', 'support@shopnest.com', 1800123456);

-- --------------------------------------------------------
-- Table structure for table `tblenquiry`  (contact form messages)
-- --------------------------------------------------------
CREATE TABLE `tblenquiry` (
  `ID` int(10) NOT NULL AUTO_INCREMENT,
  `Name` varchar(120) NOT NULL,
  `Email` varchar(120) NOT NULL,
  `MobileNumber` varchar(15) DEFAULT NULL,
  `Subject` varchar(200) DEFAULT NULL,
  `Message` mediumtext NOT NULL,
  `IsRead` tinyint(1) NOT NULL DEFAULT 0,
  `EnquiryDate` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `tblenquiry` (`Name`, `Email`, `MobileNumber`, `Subject`, `Message`, `EnquiryDate`) VALUES
('Neha Gupta', 'neha@gmail.com', '9812345678', 'Bulk order', 'Do you offer discounts for bulk orders of headphones for our office?', '2026-11-17 12:00:00');

COMMIT;
