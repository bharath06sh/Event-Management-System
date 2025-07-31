-- Add is_active column to venues table
ALTER TABLE venues ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1;

-- Add amenities columns
ALTER TABLE venues ADD COLUMN has_parking TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE venues ADD COLUMN has_wifi TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE venues ADD COLUMN has_catering TINYINT(1) NOT NULL DEFAULT 0;

-- Add image_url column
ALTER TABLE venues ADD COLUMN image_url VARCHAR(255) NULL; 