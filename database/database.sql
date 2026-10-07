-- ============================================
-- CRM DATABASE INITIALIZATION
-- PostgreSQL
-- ============================================

BEGIN;

-- ============================================
-- Cities
-- ============================================

CREATE TABLE cities (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(150) NOT NULL UNIQUE
);


-- ============================================
-- Sources
-- ============================================

CREATE TABLE sources (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
);


-- ============================================
-- Products
-- ============================================

CREATE TABLE products (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE
);


-- ============================================
-- Lead statuses
-- ============================================

CREATE TABLE statuses (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE
);


-- ============================================
-- Users / Managers
-- ============================================

CREATE TABLE users (
    id BIGSERIAL PRIMARY KEY,

    name VARCHAR(150) NOT NULL UNIQUE
);

-- ============================================
-- Leads
-- ============================================

CREATE TABLE leads (
    id BIGSERIAL PRIMARY KEY,

    external_id VARCHAR(50) NOT NULL,

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,

    phone VARCHAR(30),
    email VARCHAR(255),

    city_id BIGINT,

    source_id BIGINT,
    product_id BIGINT,
    status_id BIGINT,
    manager_id BIGINT,

    utm_campaign VARCHAR(255),

    budget_uah NUMERIC(12, 2),

    comment TEXT,

    next_contact_at TIMESTAMP,

    CONSTRAINT fk_leads_city
        FOREIGN KEY (city_id)
            REFERENCES cities(id)
            ON DELETE SET NULL,

    CONSTRAINT fk_leads_source
        FOREIGN KEY (source_id)
            REFERENCES sources(id)
            ON DELETE SET NULL,

    CONSTRAINT fk_leads_product
        FOREIGN KEY (product_id)
            REFERENCES products(id)
            ON DELETE SET NULL,

    CONSTRAINT fk_leads_status
        FOREIGN KEY (status_id)
            REFERENCES statuses(id)
            ON DELETE SET NULL,

    CONSTRAINT fk_leads_manager
        FOREIGN KEY (manager_id)
            REFERENCES users(id)
            ON DELETE SET NULL
);

CREATE INDEX idx_leads_external_id
    ON leads(external_id);

CREATE INDEX idx_leads_email
    ON leads(email);

CREATE INDEX idx_leads_phone
    ON leads(phone);

CREATE INDEX idx_leads_city
    ON leads(city_id);

CREATE INDEX idx_leads_source
    ON leads(source_id);

CREATE INDEX idx_leads_product
    ON leads(product_id);

CREATE INDEX idx_leads_status
    ON leads(status_id);

CREATE INDEX idx_leads_manager
    ON leads(manager_id);

CREATE INDEX idx_leads_created_at
    ON leads(created_at);

CREATE INDEX idx_leads_next_contact_at
    ON leads(next_contact_at);


-- ============================================
-- Initial statuses
-- ============================================

INSERT INTO statuses (name)
VALUES
('new'),
('in_progress'),
('contacted'),
('converted'),
('rejected')
ON CONFLICT (name) DO NOTHING;


-- ============================================
-- Commit
-- ============================================

COMMIT;
