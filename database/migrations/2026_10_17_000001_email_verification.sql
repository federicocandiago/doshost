-- Email confirmation for new users (EMAIL_VERIFICATION in .env).
-- Users that already exist are considered confirmed.
ALTER TABLE users
    ADD COLUMN email_verified_at DATETIME NULL AFTER password,
    ADD COLUMN verification_token CHAR(64) NULL AFTER email_verified_at,
    ADD COLUMN verification_sent_at DATETIME NULL AFTER verification_token,
    ADD INDEX idx_users_verification_token (verification_token);

UPDATE users SET email_verified_at = COALESCE(last_login_at, created_at) WHERE email_verified_at IS NULL;
