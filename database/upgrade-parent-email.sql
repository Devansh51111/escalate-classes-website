-- Existing installs only, after private backup. Fresh schema already includes these.
ALTER TABLE admission_enquiries ADD parent_email VARCHAR(254) NULL;
ALTER TABLE notification_logs ADD UNIQUE KEY unique_enquiry_notification(enquiry_id,notification_type);
