-- ============================================
-- SUPABASE MIGRATION - Trading Management System
-- Convert dari MySQL ke PostgreSQL (Supabase)
-- ============================================

-- 1. CREATE TABLE trading_positions
CREATE TABLE IF NOT EXISTS trading_positions (
  id SERIAL PRIMARY KEY,
  account_number VARCHAR(50) NOT NULL,
  ticket BIGINT NOT NULL,
  symbol VARCHAR(20) NOT NULL,
  position_type VARCHAR(10) NOT NULL, -- 'BUY' atau 'SELL'
  volume DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  price DECIMAL(15,5) NOT NULL DEFAULT 0.00000,
  profit DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  position_time TIMESTAMP WITH TIME ZONE NOT NULL,
  comment TEXT,
  sync_time TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
  created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
  CONSTRAINT unique_account_ticket UNIQUE (account_number, ticket)
);

-- Indexes untuk trading_positions
CREATE INDEX IF NOT EXISTS idx_trading_positions_account ON trading_positions(account_number);
CREATE INDEX IF NOT EXISTS idx_trading_positions_ticket ON trading_positions(ticket);
CREATE INDEX IF NOT EXISTS idx_trading_positions_position_time ON trading_positions(position_time);
CREATE INDEX IF NOT EXISTS idx_trading_positions_sync_time ON trading_positions(sync_time);
CREATE INDEX IF NOT EXISTS idx_trading_positions_comment ON trading_positions(comment);

-- 2. CREATE TABLE ea_control
CREATE TABLE IF NOT EXISTS ea_control (
  account_number VARCHAR(50) PRIMARY KEY,
  status VARCHAR(3) NOT NULL DEFAULT 'ON' CHECK (status IN ('ON', 'OFF')), -- Status EA Global
  schedule_s1 VARCHAR(3) NOT NULL DEFAULT 'ON' CHECK (schedule_s1 IN ('ON', 'OFF')),
  schedule_s2 VARCHAR(3) NOT NULL DEFAULT 'ON' CHECK (schedule_s2 IN ('ON', 'OFF')),
  schedule_s3 VARCHAR(3) NOT NULL DEFAULT 'ON' CHECK (schedule_s3 IN ('ON', 'OFF')),
  schedule_s4 VARCHAR(3) NOT NULL DEFAULT 'ON' CHECK (schedule_s4 IN ('ON', 'OFF')),
  schedule_s5 VARCHAR(3) NOT NULL DEFAULT 'ON' CHECK (schedule_s5 IN ('ON', 'OFF')),
  schedule_s6 VARCHAR(3) NOT NULL DEFAULT 'ON' CHECK (schedule_s6 IN ('ON', 'OFF')),
  schedule_s7 VARCHAR(3) NOT NULL DEFAULT 'ON' CHECK (schedule_s7 IN ('ON', 'OFF')),
  schedule_s8 VARCHAR(3) NOT NULL DEFAULT 'ON' CHECK (schedule_s8 IN ('ON', 'OFF')),
  schedule_s9 VARCHAR(3) NOT NULL DEFAULT 'ON' CHECK (schedule_s9 IN ('ON', 'OFF')),
  schedule_sx VARCHAR(3) NOT NULL DEFAULT 'ON' CHECK (schedule_sx IN ('ON', 'OFF')),
  updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
  updated_by VARCHAR(100)
);

-- Indexes untuk ea_control
CREATE INDEX IF NOT EXISTS idx_ea_control_status ON ea_control(status);
CREATE INDEX IF NOT EXISTS idx_ea_control_updated_at ON ea_control(updated_at);

-- Insert default account (jika belum ada)
INSERT INTO ea_control (
  account_number, status,
  schedule_s1, schedule_s2, schedule_s3, schedule_s4, schedule_s5,
  schedule_s6, schedule_s7, schedule_s8, schedule_s9, schedule_sx,
  updated_by
) 
VALUES (
  '206943771', 'ON',
  'ON', 'ON', 'ON', 'ON', 'ON',
  'ON', 'ON', 'ON', 'ON', 'ON',
  'system'
)
ON CONFLICT (account_number) DO NOTHING;

-- 3. CREATE TABLE lot_sizes
CREATE TABLE IF NOT EXISTS lot_sizes (
  id SERIAL PRIMARY KEY,
  schedule VARCHAR(10) NOT NULL DEFAULT 'S1' CHECK (schedule IN ('S1', 'S2', 'S3', 'S4', 'S5', 'S6', 'S7', 'S8', 'S9', 'SX')),
  size DECIMAL(10,2) NOT NULL,
  order_index INTEGER NOT NULL DEFAULT 0,
  is_active BOOLEAN NOT NULL DEFAULT true,
  created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
  updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Indexes untuk lot_sizes
CREATE INDEX IF NOT EXISTS idx_lot_sizes_schedule ON lot_sizes(schedule);
CREATE INDEX IF NOT EXISTS idx_lot_sizes_order_index ON lot_sizes(order_index);
CREATE INDEX IF NOT EXISTS idx_lot_sizes_is_active ON lot_sizes(is_active);
CREATE INDEX IF NOT EXISTS idx_lot_sizes_schedule_active ON lot_sizes(schedule, is_active);

-- Insert default lot sizes untuk setiap schedule (S1-SX)
-- Setiap schedule punya lot sizes yang berbeda sesuai pola (sama seperti MySQL PHP version)

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
  ('S1', 1.51, 16, true)
ON CONFLICT DO NOTHING;

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
  ('S2', 1.62, 16, true)
ON CONFLICT DO NOTHING;

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
  ('S3', 1.63, 16, true)
ON CONFLICT DO NOTHING;

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
  ('S4', 1.64, 16, true)
ON CONFLICT DO NOTHING;

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
  ('S5', 1.65, 16, true)
ON CONFLICT DO NOTHING;

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
  ('S6', 1.66, 16, true)
ON CONFLICT DO NOTHING;

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
  ('S7', 1.67, 16, true)
ON CONFLICT DO NOTHING;

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
  ('S8', 1.68, 16, true)
ON CONFLICT DO NOTHING;

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
  ('S9', 1.69, 16, true)
ON CONFLICT DO NOTHING;

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
  ('SX', 1.70, 16, true)
ON CONFLICT DO NOTHING;

-- Enable Row Level Security (optional - untuk development bisa disable dulu)
-- ALTER TABLE trading_positions ENABLE ROW LEVEL SECURITY;
-- ALTER TABLE ea_control ENABLE ROW LEVEL SECURITY;
-- ALTER TABLE lot_sizes ENABLE ROW LEVEL SECURITY;

-- Policy untuk allow all (untuk development)
-- CREATE POLICY "Allow all operations" ON trading_positions FOR ALL USING (true) WITH CHECK (true);
-- CREATE POLICY "Allow all operations" ON ea_control FOR ALL USING (true) WITH CHECK (true);
-- CREATE POLICY "Allow all operations" ON lot_sizes FOR ALL USING (true) WITH CHECK (true);

-- Function untuk auto update updated_at
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$ language 'plpgsql';

-- Trigger untuk auto update updated_at
CREATE TRIGGER update_lot_sizes_updated_at BEFORE UPDATE ON lot_sizes
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_ea_control_updated_at BEFORE UPDATE ON ea_control
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();


