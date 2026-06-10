-- Migrare 2026-06-10 — ruleaza pe baza de date EXISTENTA (schema.sql e pentru instalari noi).
-- psql -h localhost -p 5433 -U postgres -d babyinfo -f database/migration_2026-06-10.sql

-- 1. Atasamente PDF la evenimentele medicale (cerinta de curs)
ALTER TABLE medical_records ADD COLUMN IF NOT EXISTS document_url TEXT;

-- 2. Rate limiting pe login / reset parola / emitere token API
CREATE TABLE IF NOT EXISTS auth_attempts (
    id           SERIAL PRIMARY KEY,
    action       TEXT    NOT NULL,              -- 'login' | 'reset' | 'token'
    identifier   TEXT    NOT NULL,              -- email-ul incercat
    ip           TEXT    NOT NULL DEFAULT '',
    attempted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_auth_attempts ON auth_attempts(action, identifier, attempted_at);

-- 3. Tag-uri pe momente (cerinta gallery: "organize by date and tags")
--    Stocate ca text separat prin virgula, normalizat in MomentController.
ALTER TABLE moments ADD COLUMN IF NOT EXISTS tags TEXT NOT NULL DEFAULT '';
