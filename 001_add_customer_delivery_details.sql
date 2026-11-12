-- One-time migration for an existing database.
ALTER TABLE `orders`
  ADD COLUMN `fulfillment_type` ENUM('pickup', 'delivery') NOT NULL DEFAULT 'pickup' AFTER `payment_mode`,
  ADD COLUMN `delivery_address` TEXT DEFAULT NULL AFTER `fulfillment_type`;
