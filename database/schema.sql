
-- ── Users ──
CREATE TABLE IF NOT EXISTS users (
                                     id             SERIAL PRIMARY KEY,
                                     first_name     TEXT    NOT NULL,
                                     last_name      TEXT    NOT NULL,
                                     email          TEXT    NOT NULL UNIQUE,
                                     password_hash  TEXT    NOT NULL,
                                     role           TEXT    NOT NULL DEFAULT 'viewer'
                                         CHECK(role IN ('owner','coparent','caregiver','viewer')),
                                     is_superadmin  BOOLEAN NOT NULL DEFAULT FALSE,
                                     banned_at      TIMESTAMP,
                                     ban_reason     TEXT,
                                     theme          TEXT    NOT NULL DEFAULT 'boy'
                                         CHECK(theme IN ('boy','girl')),
                                     avatar_color   TEXT    NOT NULL DEFAULT 'c1',
                                     created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                     updated_at     TIMESTAMP
);

-- ── Password resets ──
CREATE TABLE IF NOT EXISTS password_resets (
                                               id         SERIAL PRIMARY KEY,
                                               user_id    INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
                                               code       TEXT    NOT NULL,
                                               expires_at TIMESTAMP NOT NULL,
                                               created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- ── Children ──
CREATE TABLE IF NOT EXISTS children (
                                        id           SERIAL PRIMARY KEY,
                                        first_name   TEXT    NOT NULL,
                                        last_name    TEXT,
                                        date_of_birth DATE   NOT NULL,
                                        gender       TEXT    CHECK(gender IN ('M','F','other')),
                                        blood_type   TEXT,
                                        avatar_color TEXT    NOT NULL DEFAULT 'c1',
                                        photo_url    TEXT,
                                        notes        TEXT,
                                        created_by   INTEGER NOT NULL REFERENCES users(id),
                                        created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                        updated_at   TIMESTAMP
);

-- ── Family members ──
CREATE TABLE IF NOT EXISTS family_members (
                                              id          SERIAL PRIMARY KEY,
                                              child_id    INTEGER NOT NULL REFERENCES children(id) ON DELETE CASCADE,
                                              user_id     INTEGER NOT NULL REFERENCES users(id)    ON DELETE CASCADE,
                                              permission  TEXT    NOT NULL DEFAULT 'viewer'
                                                  CHECK(permission IN ('owner','coparent','caregiver','viewer')),
                                              joined_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                              UNIQUE(child_id, user_id)
);

-- ── Invitations (token-uri unice pentru invitații) ──
CREATE TABLE IF NOT EXISTS invitations (
                                           id          SERIAL PRIMARY KEY,
                                           child_id    INTEGER NOT NULL REFERENCES children(id) ON DELETE CASCADE,
                                           invited_by  INTEGER NOT NULL REFERENCES users(id),
                                           token       TEXT    NOT NULL UNIQUE,
                                           email       TEXT,
                                           permission  TEXT    NOT NULL DEFAULT 'viewer'
                                               CHECK(permission IN ('owner','coparent','caregiver','viewer')),
                                           expires_at  TIMESTAMP NOT NULL,
                                           used_at     TIMESTAMP,
                                           used_by     INTEGER REFERENCES users(id),
                                           created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- ── Feedings ──
CREATE TABLE IF NOT EXISTS feedings (
                                        id          SERIAL PRIMARY KEY,
                                        child_id    INTEGER NOT NULL REFERENCES children(id) ON DELETE CASCADE,
                                        logged_by   INTEGER NOT NULL REFERENCES users(id),
                                        type        TEXT    NOT NULL CHECK(type IN ('breast','bottle','solids')),
                                        side        TEXT    CHECK(side IN ('L','R','both')),
                                        duration_min INTEGER,
                                        amount_ml   INTEGER,
                                        food_desc   TEXT,
                                        notes       TEXT,
                                        fed_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                        created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- ── Sleep logs ──
CREATE TABLE IF NOT EXISTS sleep_logs (
                                          id          SERIAL PRIMARY KEY,
                                          child_id    INTEGER NOT NULL REFERENCES children(id) ON DELETE CASCADE,
                                          logged_by   INTEGER NOT NULL REFERENCES users(id),
                                          type        TEXT    NOT NULL DEFAULT 'night' CHECK(type IN ('night','nap')),
                                          started_at  TIMESTAMP NOT NULL,
                                          ended_at    TIMESTAMP,
                                          quality     INTEGER CHECK(quality BETWEEN 1 AND 5),
                                          notes       TEXT,
                                          created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- ── Timeline moments ──
CREATE TABLE IF NOT EXISTS moments (
                                       id          SERIAL PRIMARY KEY,
                                       child_id    INTEGER NOT NULL REFERENCES children(id) ON DELETE CASCADE,
                                       logged_by   INTEGER NOT NULL REFERENCES users(id),
                                       type        TEXT    NOT NULL CHECK(type IN ('milestone','food','medical','photo','friends','sleep','voice','other')),
                                       title       TEXT    NOT NULL,
                                       body        TEXT,
                                       is_pinned   INTEGER NOT NULL DEFAULT 0,
                                       is_shared   INTEGER NOT NULL DEFAULT 0,
                                       share_token TEXT    UNIQUE,
                                       reactions   INTEGER NOT NULL DEFAULT 0,
                                       happened_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                       created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- ── Comments (comentarii text pe postări) ──
CREATE TABLE IF NOT EXISTS comments (
                                        id          SERIAL PRIMARY KEY,
                                        moment_id   INTEGER NOT NULL REFERENCES moments(id) ON DELETE CASCADE,
                                        user_id     INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
                                        body        TEXT    NOT NULL,
                                        created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                        updated_at  TIMESTAMP
);

-- ── Reactions (reacții emoji prestabilite) ──
CREATE TABLE IF NOT EXISTS reactions (
                                         id          SERIAL PRIMARY KEY,
                                         moment_id   INTEGER NOT NULL REFERENCES moments(id) ON DELETE CASCADE,
                                         user_id     INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
                                         emoji_type  TEXT    NOT NULL CHECK(emoji_type IN ('like','heart','smile','star','laugh')),
                                         created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                         UNIQUE(moment_id, user_id, emoji_type)
);

-- ── Media ──
CREATE TABLE IF NOT EXISTS media (
                                     id          SERIAL PRIMARY KEY,
                                     child_id    INTEGER NOT NULL REFERENCES children(id) ON DELETE CASCADE,
                                     moment_id   INTEGER REFERENCES moments(id) ON DELETE SET NULL,
                                     uploaded_by INTEGER NOT NULL REFERENCES users(id),
                                     type        TEXT    NOT NULL CHECK(type IN ('photo','video','audio')),
                                     filename    TEXT    NOT NULL,
                                     original_name TEXT  NOT NULL,
                                     size_bytes  INTEGER,
                                     mime_type   TEXT,
                                     caption     TEXT,
                                     taken_at    TIMESTAMP,
                                     created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- ── Medical records ──
CREATE TABLE IF NOT EXISTS medical_records (
                                               id          SERIAL PRIMARY KEY,
                                               child_id    INTEGER NOT NULL REFERENCES children(id) ON DELETE CASCADE,
                                               logged_by   INTEGER NOT NULL REFERENCES users(id),
                                               type        TEXT    NOT NULL CHECK(type IN ('vaccine','visit','allergy','medication','measurement','other')),
                                               title       TEXT    NOT NULL,
                                               description TEXT,
                                               doctor_name TEXT,
                                               clinic_name TEXT,
                                               date_at     DATE    NOT NULL,
                                               next_date   DATE,
                                               created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- ── Growth measurements ──
CREATE TABLE IF NOT EXISTS growth (
                                      id          SERIAL PRIMARY KEY,
                                      child_id    INTEGER NOT NULL REFERENCES children(id) ON DELETE CASCADE,
                                      logged_by   INTEGER NOT NULL REFERENCES users(id),
                                      weight_kg   NUMERIC(5,2),
                                      height_cm   NUMERIC(5,2),
                                      head_cm     NUMERIC(5,2),
                                      measured_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                      created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- ── Relationships ──
CREATE TABLE IF NOT EXISTS relationships (
                                             id          SERIAL PRIMARY KEY,
                                             child_id    INTEGER NOT NULL REFERENCES children(id) ON DELETE CASCADE,
                                             name        TEXT    NOT NULL,
                                             relationship TEXT   NOT NULL,
                                             group_type  TEXT    NOT NULL DEFAULT 'friends'
                                                 CHECK(group_type IN ('family','daycare','friends','other')),
                                             age_years   INTEGER,
                                             notes       TEXT,
                                             avatar_color TEXT   DEFAULT 'c1',
                                             added_by    INTEGER NOT NULL REFERENCES users(id),
                                             created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- ── Interaction logs ──
CREATE TABLE IF NOT EXISTS interactions (
                                            id              SERIAL PRIMARY KEY,
                                            child_id        INTEGER NOT NULL REFERENCES children(id) ON DELETE CASCADE,
                                            relationship_id INTEGER NOT NULL REFERENCES relationships(id) ON DELETE CASCADE,
                                            moment_id       INTEGER REFERENCES moments(id) ON DELETE SET NULL,
                                            description     TEXT,
                                            interacted_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                            created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- ── Indecși performanță ──
CREATE INDEX IF NOT EXISTS idx_feedings_child    ON feedings(child_id, fed_at);
CREATE INDEX IF NOT EXISTS idx_sleep_child       ON sleep_logs(child_id, started_at);
CREATE INDEX IF NOT EXISTS idx_moments_child     ON moments(child_id, happened_at);
CREATE INDEX IF NOT EXISTS idx_moments_shared    ON moments(child_id, is_shared);
CREATE INDEX IF NOT EXISTS idx_media_child       ON media(child_id);
CREATE INDEX IF NOT EXISTS idx_medical_child     ON medical_records(child_id, date_at);
CREATE INDEX IF NOT EXISTS idx_growth_child      ON growth(child_id, measured_at);
CREATE INDEX IF NOT EXISTS idx_family_child      ON family_members(child_id);
CREATE INDEX IF NOT EXISTS idx_relationships_ch  ON relationships(child_id);

-- Indecși noi pentru funcționalitățile adăugate
CREATE INDEX IF NOT EXISTS idx_invitations_token ON invitations(token);
CREATE INDEX IF NOT EXISTS idx_comments_moment ON comments(moment_id);
CREATE INDEX IF NOT EXISTS idx_reactions_moment ON reactions(moment_id);
CREATE INDEX IF NOT EXISTS idx_users_superadmin ON users(is_superadmin) WHERE is_superadmin = TRUE;
CREATE INDEX IF NOT EXISTS idx_users_banned    ON users(banned_at) WHERE banned_at IS NOT NULL;