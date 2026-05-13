PRAGMA foreign_keys = ON;
PRAGMA journal_mode  = WAL;

-- ──Users
CREATE TABLE IF NOT EXISTS users (
                                     id             INTEGER PRIMARY KEY AUTOINCREMENT,
                                     first_name     TEXT    NOT NULL,
                                     last_name      TEXT    NOT NULL,
                                     email          TEXT    NOT NULL UNIQUE,
                                     password_hash  TEXT    NOT NULL,
                                     role           TEXT    NOT NULL DEFAULT 'viewer'
                                         CHECK(role IN ('owner','coparent','caregiver','viewer')),
                                     avatar_color   TEXT    NOT NULL DEFAULT 'c1',
                                     created_at     TEXT    NOT NULL DEFAULT (datetime('now')),
                                     updated_at     TEXT
);

-- ──Password resets
CREATE TABLE IF NOT EXISTS password_resets (
                                               id         INTEGER PRIMARY KEY AUTOINCREMENT,
                                               user_id    INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
                                               code       TEXT    NOT NULL,
                                               expires_at TEXT    NOT NULL,
                                               created_at TEXT    NOT NULL DEFAULT (datetime('now'))
);

-- ──Children
CREATE TABLE IF NOT EXISTS children (
                                        id           INTEGER PRIMARY KEY AUTOINCREMENT,
                                        first_name   TEXT    NOT NULL,
                                        last_name    TEXT,
                                        date_of_birth TEXT   NOT NULL,
                                        gender       TEXT    CHECK(gender IN ('M','F','other')),
                                        blood_type   TEXT,
                                        avatar_color TEXT    NOT NULL DEFAULT 'c1',
                                        notes        TEXT,
                                        created_by   INTEGER NOT NULL REFERENCES users(id),
                                        created_at   TEXT    NOT NULL DEFAULT (datetime('now')),
                                        updated_at   TEXT
);

-- ──Family members
CREATE TABLE IF NOT EXISTS family_members (
                                              id          INTEGER PRIMARY KEY AUTOINCREMENT,
                                              child_id    INTEGER NOT NULL REFERENCES children(id) ON DELETE CASCADE,
                                              user_id     INTEGER NOT NULL REFERENCES users(id)    ON DELETE CASCADE,
                                              permission  TEXT    NOT NULL DEFAULT 'viewer'
                                                  CHECK(permission IN ('owner','coparent','caregiver','viewer')),
                                              joined_at   TEXT    NOT NULL DEFAULT (datetime('now')),
                                              UNIQUE(child_id, user_id)
);

-- ──Feedings
CREATE TABLE IF NOT EXISTS feedings (
                                        id          INTEGER PRIMARY KEY AUTOINCREMENT,
                                        child_id    INTEGER NOT NULL REFERENCES children(id) ON DELETE CASCADE,
                                        logged_by   INTEGER NOT NULL REFERENCES users(id),
                                        type        TEXT    NOT NULL CHECK(type IN ('breast','bottle','solids')),
                                        side        TEXT    CHECK(side IN ('L','R','both')),
                                        duration_min INTEGER,
                                        amount_ml   INTEGER,
                                        food_desc   TEXT,
                                        notes       TEXT,
                                        fed_at      TEXT    NOT NULL DEFAULT (datetime('now')),
                                        created_at  TEXT    NOT NULL DEFAULT (datetime('now'))
);

-- ──Sleep logs
CREATE TABLE IF NOT EXISTS sleep_logs (
                                          id          INTEGER PRIMARY KEY AUTOINCREMENT,
                                          child_id    INTEGER NOT NULL REFERENCES children(id) ON DELETE CASCADE,
                                          logged_by   INTEGER NOT NULL REFERENCES users(id),
                                          type        TEXT    NOT NULL DEFAULT 'night' CHECK(type IN ('night','nap')),
                                          started_at  TEXT    NOT NULL,
                                          ended_at    TEXT,
                                          quality     INTEGER CHECK(quality BETWEEN 1 AND 5),
                                          notes       TEXT,
                                          created_at  TEXT    NOT NULL DEFAULT (datetime('now'))
);

-- ──Timeline moments
CREATE TABLE IF NOT EXISTS moments (
                                       id          INTEGER PRIMARY KEY AUTOINCREMENT,
                                       child_id    INTEGER NOT NULL REFERENCES children(id) ON DELETE CASCADE,
                                       logged_by   INTEGER NOT NULL REFERENCES users(id),
                                       type        TEXT    NOT NULL CHECK(type IN ('milestone','food','medical','photo','friends','sleep','voice','other')),
                                       title       TEXT    NOT NULL,
                                       body        TEXT,
                                       is_pinned   INTEGER NOT NULL DEFAULT 0,
                                       is_shared   INTEGER NOT NULL DEFAULT 0,  -- apare in RSS daca 1
                                       reactions   INTEGER NOT NULL DEFAULT 0,
                                       happened_at TEXT    NOT NULL DEFAULT (datetime('now')),
                                       created_at  TEXT    NOT NULL DEFAULT (datetime('now'))
);

-- ──Media
CREATE TABLE IF NOT EXISTS media (
                                     id          INTEGER PRIMARY KEY AUTOINCREMENT,
                                     child_id    INTEGER NOT NULL REFERENCES children(id) ON DELETE CASCADE,
                                     moment_id   INTEGER REFERENCES moments(id) ON DELETE SET NULL,
                                     uploaded_by INTEGER NOT NULL REFERENCES users(id),
                                     type        TEXT    NOT NULL CHECK(type IN ('photo','video','audio')),
                                     filename    TEXT    NOT NULL,
                                     original_name TEXT  NOT NULL,
                                     size_bytes  INTEGER,
                                     mime_type   TEXT,
                                     caption     TEXT,
                                     taken_at    TEXT,
                                     created_at  TEXT    NOT NULL DEFAULT (datetime('now'))
);

-- ──Medical records
CREATE TABLE IF NOT EXISTS medical_records (
                                               id          INTEGER PRIMARY KEY AUTOINCREMENT,
                                               child_id    INTEGER NOT NULL REFERENCES children(id) ON DELETE CASCADE,
                                               logged_by   INTEGER NOT NULL REFERENCES users(id),
                                               type        TEXT    NOT NULL CHECK(type IN ('vaccine','visit','allergy','medication','measurement','other')),
                                               title       TEXT    NOT NULL,
                                               description TEXT,
                                               doctor_name TEXT,
                                               clinic_name TEXT,
                                               date_at     TEXT    NOT NULL,
                                               next_date   TEXT,
                                               created_at  TEXT    NOT NULL DEFAULT (datetime('now'))
);

-- ──Growth measurements
CREATE TABLE IF NOT EXISTS growth (
                                      id          INTEGER PRIMARY KEY AUTOINCREMENT,
                                      child_id    INTEGER NOT NULL REFERENCES children(id) ON DELETE CASCADE,
                                      logged_by   INTEGER NOT NULL REFERENCES users(id),
                                      weight_kg   REAL,
                                      height_cm   REAL,
                                      head_cm     REAL,
                                      measured_at TEXT    NOT NULL DEFAULT (datetime('now')),
                                      created_at  TEXT    NOT NULL DEFAULT (datetime('now'))
);

-- ──Relationships
CREATE TABLE IF NOT EXISTS relationships (
                                             id          INTEGER PRIMARY KEY AUTOINCREMENT,
                                             child_id    INTEGER NOT NULL REFERENCES children(id) ON DELETE CASCADE,
                                             name        TEXT    NOT NULL,
                                             relationship TEXT   NOT NULL,  -- ex: cousin, classmate, neighbor
                                             group_type  TEXT    NOT NULL DEFAULT 'friends'
                                                 CHECK(group_type IN ('family','daycare','friends','other')),
                                             age_years   INTEGER,
                                             notes       TEXT,
                                             avatar_color TEXT   DEFAULT 'c1',
                                             added_by    INTEGER NOT NULL REFERENCES users(id),
                                             created_at  TEXT    NOT NULL DEFAULT (datetime('now'))
);

-- ──Interaction logs
CREATE TABLE IF NOT EXISTS interactions (
                                            id              INTEGER PRIMARY KEY AUTOINCREMENT,
                                            child_id        INTEGER NOT NULL REFERENCES children(id) ON DELETE CASCADE,
                                            relationship_id INTEGER NOT NULL REFERENCES relationships(id) ON DELETE CASCADE,
                                            moment_id       INTEGER REFERENCES moments(id) ON DELETE SET NULL,
                                            description     TEXT,
                                            interacted_at   TEXT    NOT NULL DEFAULT (datetime('now')),
                                            created_at      TEXT    NOT NULL DEFAULT (datetime('now'))
);

--Indecsi pentru performanta
CREATE INDEX IF NOT EXISTS idx_feedings_child   ON feedings(child_id, fed_at);
CREATE INDEX IF NOT EXISTS idx_sleep_child      ON sleep_logs(child_id, started_at);
CREATE INDEX IF NOT EXISTS idx_moments_child    ON moments(child_id, happened_at);
CREATE INDEX IF NOT EXISTS idx_moments_shared   ON moments(child_id, is_shared);
CREATE INDEX IF NOT EXISTS idx_media_child      ON media(child_id);
CREATE INDEX IF NOT EXISTS idx_medical_child    ON medical_records(child_id, date_at);
CREATE INDEX IF NOT EXISTS idx_growth_child     ON growth(child_id, measured_at);
CREATE INDEX IF NOT EXISTS idx_family_child     ON family_members(child_id);
CREATE INDEX IF NOT EXISTS idx_relationships_ch ON relationships(child_id);