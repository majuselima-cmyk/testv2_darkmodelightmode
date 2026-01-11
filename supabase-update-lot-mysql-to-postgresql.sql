-- ============================================
-- UPDATE MIGRATION: MySQL PHP to Supabase PostgreSQL
-- Update tabel lot_sizes untuk support per-schedule (S1-SX)
-- Convert dari MySQL ke PostgreSQL syntax
-- ============================================

-- 1. Tambah kolom schedule ke tabel lot_sizes (jika belum ada)
-- PostgreSQL menggunakan DO block untuk check column existence
DO $$ 
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns 
    WHERE table_name = 'lot_sizes' 
    AND column_name = 'schedule'
    AND table_schema = 'public'
  ) THEN
    ALTER TABLE lot_sizes 
    ADD COLUMN schedule VARCHAR(10) NOT NULL DEFAULT 'S1';
  END IF;
END $$;

-- 2. Tambah constraint check untuk schedule (jika belum ada)
DO $$
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.table_constraints 
    WHERE constraint_name = 'lot_sizes_schedule_check'
    AND table_name = 'lot_sizes'
    AND table_schema = 'public'
  ) THEN
    ALTER TABLE lot_sizes 
    ADD CONSTRAINT lot_sizes_schedule_check 
    CHECK (schedule IN ('S1', 'S2', 'S3', 'S4', 'S5', 'S6', 'S7', 'S8', 'S9', 'SX'));
  END IF;
END $$;

-- 3. Update data existing: Set semua lot sizes existing ke S1 (default)
-- Hanya update jika schedule IS NULL atau empty string
UPDATE lot_sizes 
SET schedule = 'S1' 
WHERE schedule IS NULL OR schedule = '';

-- 4. Tambah index untuk schedule (jika belum ada)
CREATE INDEX IF NOT EXISTS idx_lot_sizes_schedule ON lot_sizes(schedule);
CREATE INDEX IF NOT EXISTS idx_lot_sizes_schedule_active ON lot_sizes(schedule, is_active);

-- 5. HAPUS SEMUA DATA LAMA (seperti di MySQL version)
-- Reset data untuk insert data baru dengan pola yang benar
DELETE FROM lot_sizes;

-- Reset sequence (PostgreSQL equivalent of AUTO_INCREMENT)
ALTER SEQUENCE IF EXISTS lot_sizes_id_seq RESTART WITH 1;

-- 6. INSERT DATA BARU DENGAN POLA YANG SAMA SEPERTI MySQL PHP VERSION
-- Setiap schedule punya lot sizes yang berbeda sesuai pola

-- S1: Starting from 0.01, 0.11, 0.21, etc.
INSERT INTO lot_sizes (schedule, size, order_index, is_active) VALUES
  ('S1', 0.01, 1, true),
  ('S1', 0.11, 2, true),
  ('S1', 0.21, 3, true),
  ('S1', 0.31, 4, true),
  ('S1', 0.41, 5, true),
  ('S1', 0.51, 6, true),
  ('S1', 0.61, 7, true),
  ('S1', 0.71, 8, true),
  ('S1', 0.81, 9, true),
  ('S1', 0.91, 10, true),
  ('S1', 1.01, 11, true),
  ('S1', 1.11, 12, true),
  ('S1', 1.21, 13, true),
  ('S1', 1.31, 14, true),
  ('S1', 1.41, 15, true),
  ('S1', 1.51, 16, true);

-- S2: Starting from 0.02, 0.22, 0.32, etc.
INSERT INTO lot_sizes (schedule, size, order_index, is_active) VALUES
  ('S2', 0.02, 1, true),
  ('S2', 0.22, 2, true),
  ('S2', 0.32, 3, true),
  ('S2', 0.42, 4, true),
  ('S2', 0.52, 5, true),
  ('S2', 0.62, 6, true),
  ('S2', 0.72, 7, true),
  ('S2', 0.82, 8, true),
  ('S2', 0.92, 9, true),
  ('S2', 1.02, 10, true),
  ('S2', 1.12, 11, true),
  ('S2', 1.22, 12, true),
  ('S2', 1.32, 13, true),
  ('S2', 1.42, 14, true),
  ('S2', 1.52, 15, true),
  ('S2', 1.62, 16, true);

-- S3: Starting from 0.03, 0.23, 0.33, etc.
INSERT INTO lot_sizes (schedule, size, order_index, is_active) VALUES
  ('S3', 0.03, 1, true),
  ('S3', 0.23, 2, true),
  ('S3', 0.33, 3, true),
  ('S3', 0.43, 4, true),
  ('S3', 0.53, 5, true),
  ('S3', 0.63, 6, true),
  ('S3', 0.73, 7, true),
  ('S3', 0.83, 8, true),
  ('S3', 0.93, 9, true),
  ('S3', 1.03, 10, true),
  ('S3', 1.13, 11, true),
  ('S3', 1.23, 12, true),
  ('S3', 1.33, 13, true),
  ('S3', 1.43, 14, true),
  ('S3', 1.53, 15, true),
  ('S3', 1.63, 16, true);

-- S4: Starting from 0.04, 0.24, 0.34, etc.
INSERT INTO lot_sizes (schedule, size, order_index, is_active) VALUES
  ('S4', 0.04, 1, true),
  ('S4', 0.24, 2, true),
  ('S4', 0.34, 3, true),
  ('S4', 0.44, 4, true),
  ('S4', 0.54, 5, true),
  ('S4', 0.64, 6, true),
  ('S4', 0.74, 7, true),
  ('S4', 0.84, 8, true),
  ('S4', 0.94, 9, true),
  ('S4', 1.04, 10, true),
  ('S4', 1.14, 11, true),
  ('S4', 1.24, 12, true),
  ('S4', 1.34, 13, true),
  ('S4', 1.44, 14, true),
  ('S4', 1.54, 15, true),
  ('S4', 1.64, 16, true);

-- S5: Starting from 0.05, 0.25, 0.35, etc.
INSERT INTO lot_sizes (schedule, size, order_index, is_active) VALUES
  ('S5', 0.05, 1, true),
  ('S5', 0.25, 2, true),
  ('S5', 0.35, 3, true),
  ('S5', 0.45, 4, true),
  ('S5', 0.55, 5, true),
  ('S5', 0.65, 6, true),
  ('S5', 0.75, 7, true),
  ('S5', 0.85, 8, true),
  ('S5', 0.95, 9, true),
  ('S5', 1.05, 10, true),
  ('S5', 1.15, 11, true),
  ('S5', 1.25, 12, true),
  ('S5', 1.35, 13, true),
  ('S5', 1.45, 14, true),
  ('S5', 1.55, 15, true),
  ('S5', 1.65, 16, true);

-- S6: Starting from 0.06, 0.26, 0.36, etc.
INSERT INTO lot_sizes (schedule, size, order_index, is_active) VALUES
  ('S6', 0.06, 1, true),
  ('S6', 0.26, 2, true),
  ('S6', 0.36, 3, true),
  ('S6', 0.46, 4, true),
  ('S6', 0.56, 5, true),
  ('S6', 0.66, 6, true),
  ('S6', 0.76, 7, true),
  ('S6', 0.86, 8, true),
  ('S6', 0.96, 9, true),
  ('S6', 1.06, 10, true),
  ('S6', 1.16, 11, true),
  ('S6', 1.26, 12, true),
  ('S6', 1.36, 13, true),
  ('S6', 1.46, 14, true),
  ('S6', 1.56, 15, true),
  ('S6', 1.66, 16, true);

-- S7: Starting from 0.07, 0.27, 0.37, etc.
INSERT INTO lot_sizes (schedule, size, order_index, is_active) VALUES
  ('S7', 0.07, 1, true),
  ('S7', 0.27, 2, true),
  ('S7', 0.37, 3, true),
  ('S7', 0.47, 4, true),
  ('S7', 0.57, 5, true),
  ('S7', 0.67, 6, true),
  ('S7', 0.77, 7, true),
  ('S7', 0.87, 8, true),
  ('S7', 0.97, 9, true),
  ('S7', 1.07, 10, true),
  ('S7', 1.17, 11, true),
  ('S7', 1.27, 12, true),
  ('S7', 1.37, 13, true),
  ('S7', 1.47, 14, true),
  ('S7', 1.57, 15, true),
  ('S7', 1.67, 16, true);

-- S8: Starting from 0.08, 0.28, 0.38, etc.
INSERT INTO lot_sizes (schedule, size, order_index, is_active) VALUES
  ('S8', 0.08, 1, true),
  ('S8', 0.28, 2, true),
  ('S8', 0.38, 3, true),
  ('S8', 0.48, 4, true),
  ('S8', 0.58, 5, true),
  ('S8', 0.68, 6, true),
  ('S8', 0.78, 7, true),
  ('S8', 0.88, 8, true),
  ('S8', 0.98, 9, true),
  ('S8', 1.08, 10, true),
  ('S8', 1.18, 11, true),
  ('S8', 1.28, 12, true),
  ('S8', 1.38, 13, true),
  ('S8', 1.48, 14, true),
  ('S8', 1.58, 15, true),
  ('S8', 1.68, 16, true);

-- S9: Starting from 0.09, 0.29, 0.39, etc.
INSERT INTO lot_sizes (schedule, size, order_index, is_active) VALUES
  ('S9', 0.09, 1, true),
  ('S9', 0.29, 2, true),
  ('S9', 0.39, 3, true),
  ('S9', 0.49, 4, true),
  ('S9', 0.59, 5, true),
  ('S9', 0.69, 6, true),
  ('S9', 0.79, 7, true),
  ('S9', 0.89, 8, true),
  ('S9', 0.99, 9, true),
  ('S9', 1.09, 10, true),
  ('S9', 1.19, 11, true),
  ('S9', 1.29, 12, true),
  ('S9', 1.39, 13, true),
  ('S9', 1.49, 14, true),
  ('S9', 1.59, 15, true),
  ('S9', 1.69, 16, true);

-- SX: Starting from 0.10, 0.30, 0.40, etc.
INSERT INTO lot_sizes (schedule, size, order_index, is_active) VALUES
  ('SX', 0.10, 1, true),
  ('SX', 0.30, 2, true),
  ('SX', 0.40, 3, true),
  ('SX', 0.50, 4, true),
  ('SX', 0.60, 5, true),
  ('SX', 0.70, 6, true),
  ('SX', 0.80, 7, true),
  ('SX', 0.90, 8, true),
  ('SX', 1.00, 9, true),
  ('SX', 1.10, 10, true),
  ('SX', 1.20, 11, true),
  ('SX', 1.30, 12, true),
  ('SX', 1.40, 13, true),
  ('SX', 1.50, 14, true),
  ('SX', 1.60, 15, true),
  ('SX', 1.70, 16, true);

-- ============================================
-- VERIFICATION QUERY (Optional - uncomment untuk test)
-- ============================================
/*
-- Tampilkan ringkasan per schedule (PostgreSQL version)
SELECT 
  schedule,
  COUNT(*) as total_entries,
  MIN(size) as min_lot,
  MAX(size) as max_lot,
  STRING_AGG(size::text ORDER BY order_index, ', ') as lot_sequence
FROM lot_sizes 
WHERE is_active = true
GROUP BY schedule 
ORDER BY 
  CASE schedule
    WHEN 'S1' THEN 1
    WHEN 'S2' THEN 2
    WHEN 'S3' THEN 3
    WHEN 'S4' THEN 4
    WHEN 'S5' THEN 5
    WHEN 'S6' THEN 6
    WHEN 'S7' THEN 7
    WHEN 'S8' THEN 8
    WHEN 'S9' THEN 9
    WHEN 'SX' THEN 10
  END;

-- Check total data
SELECT 'Total Data' as info, COUNT(*)::text as count FROM lot_sizes
UNION ALL
SELECT 'Active Data' as info, COUNT(*)::text as count FROM lot_sizes WHERE is_active = true
UNION ALL
SELECT 'Schedules' as info, COUNT(DISTINCT schedule)::text as count FROM lot_sizes;
*/

-- ============================================
-- NOTES:
-- ============================================
-- 1. Script ini aman dijalankan multiple times (idempotent)
-- 2. Menggunakan IF NOT EXISTS untuk menghindari error
-- 3. Data existing akan di-set ke S1 jika schedule NULL/empty
-- 4. Default lot sizes akan di-copy ke semua schedule (S1-SX)
-- 5. Compatible dengan PostgreSQL/Supabase syntax
-- 6. Tidak akan menghapus data existing, hanya menambahkan/update

