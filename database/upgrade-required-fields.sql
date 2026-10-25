-- Existing installations only. Run once after a private backup. New installs already include this column.
ALTER TABLE admission_enquiries ADD source VARCHAR(40) NOT NULL DEFAULT 'website';
