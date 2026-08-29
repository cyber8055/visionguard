-- Vision Guard Database Schema
-- MySQL 8.0+
-- Database: vision_guard_db
--
-- This schema is based on the uploaded Vision Guard/PTW specification and
-- the currently supplied JSON data structures. Dynamic PTW/JSA details are
-- kept in normalized child tables plus JSON where the source specification
-- does not define a fixed relational field list.

CREATE DATABASE IF NOT EXISTS vision_guard_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE vision_guard_db;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS audit_events;
DROP TABLE IF EXISTS auth_logs;
DROP TABLE IF EXISTS sessions;
DROP TABLE IF EXISTS closures;
DROP TABLE IF EXISTS extensions;
DROP TABLE IF EXISTS renewals;
DROP TABLE IF EXISTS approvals;
DROP TABLE IF EXISTS handover_records;
DROP TABLE IF EXISTS emergency_procedures;
DROP TABLE IF EXISTS documents;
DROP TABLE IF EXISTS isolation_points;
DROP TABLE IF EXISTS loto_records;
DROP TABLE IF EXISTS ppe_requirements;
DROP TABLE IF EXISTS ppe_matrix;
DROP TABLE IF EXISTS gas_tests;
DROP TABLE IF EXISTS checklist_responses;
DROP TABLE IF EXISTS checklist_items;
DROP TABLE IF EXISTS jsa_records;
DROP TABLE IF EXISTS permit_workers;
DROP TABLE IF EXISTS workers;
DROP TABLE IF EXISTS permits;
DROP TABLE IF EXISTS permit_types;
DROP TABLE IF EXISTS incidents;
DROP TABLE IF EXISTS plant_config;
DROP TABLE IF EXISTS webhooks;
DROP TABLE IF EXISTS retention_policy;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS roles;

SET FOREIGN_KEY_CHECKS = 1;

-- =========================================================
-- AUTHENTICATION / RBAC
-- =========================================================

CREATE TABLE roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO roles (name, description) VALUES
('Admin', 'System administrator'),
('Manager', 'Operations manager and approval role'),
('Supervisor', 'Permit initiation and assigned-work role'),
('Safety Officer', 'Safety verification role'),
('Worker', 'Worker/permit participant');

CREATE TABLE users (
    id VARCHAR(64) PRIMARY KEY,
    role_id BIGINT UNSIGNED NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    name VARCHAR(150) NOT NULL,
    plant VARCHAR(100) NULL,
    reports_to VARCHAR(64) NULL,
    is_online BOOLEAN NOT NULL DEFAULT FALSE,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    last_login_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_users_role
        FOREIGN KEY (role_id) REFERENCES roles(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_users_reports_to
        FOREIGN KEY (reports_to) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    INDEX idx_users_role (role_id),
    INDEX idx_users_plant (plant),
    INDEX idx_users_online (is_online)
) ENGINE=InnoDB;

CREATE TABLE sessions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(64) NOT NULL,
    session_token_hash VARCHAR(255) NOT NULL UNIQUE,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_activity_at DATETIME NULL,
    expires_at DATETIME NOT NULL,
    revoked_at DATETIME NULL,
    CONSTRAINT fk_sessions_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    INDEX idx_sessions_user (user_id),
    INDEX idx_sessions_expires (expires_at)
) ENGINE=InnoDB;

CREATE TABLE auth_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(64) NULL,
    email_attempted VARCHAR(255) NULL,
    action VARCHAR(50) NOT NULL,
    details TEXT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_auth_logs_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    INDEX idx_auth_logs_user (user_id),
    INDEX idx_auth_logs_created (created_at),
    INDEX idx_auth_logs_action (action)
) ENGINE=InnoDB;

-- =========================================================
-- INCIDENTS / OPERATIONS MONITORING
-- =========================================================

CREATE TABLE incidents (
    id VARCHAR(64) PRIMARY KEY,
    incident_timestamp DATETIME NOT NULL,
    severity VARCHAR(30) NOT NULL,
    type VARCHAR(100) NOT NULL,
    plant VARCHAR(100) NULL,
    location VARCHAR(255) NOT NULL,
    reported_by VARCHAR(255) NULL,
    status VARCHAR(50) NOT NULL,
    description TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_incidents_timestamp (incident_timestamp),
    INDEX idx_incidents_plant (plant),
    INDEX idx_incidents_severity (severity),
    INDEX idx_incidents_status (status)
) ENGINE=InnoDB;

-- =========================================================
-- PTW CONFIGURATION
-- =========================================================

CREATE TABLE permit_types (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL UNIQUE,
    code VARCHAR(60) NULL UNIQUE,
    description TEXT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    configuration JSON NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO permit_types (name, code, description) VALUES
('Confined Space/Hazardous Space', 'CONFINED_SPACE', 'Confined or hazardous space permit'),
('Excavation/Digging/Floor Breaking', 'EXCAVATION', 'Excavation, digging and floor-breaking permit'),
('Electrical Work', 'ELECTRICAL', 'Electrical work permit'),
('Fragile Roof', 'FRAGILE_ROOF', 'Fragile roof/work-at-height permit'),
('Hot Work', 'HOT_WORK', 'Hot work permit'),
('Cold Work', 'COLD_WORK', 'Cold work permit'),
('Working at Height', 'WORKING_AT_HEIGHT', 'Working at height permit'),
('Heavy Lifting/Crane Operations', 'HEAVY_LIFTING', 'Heavy lifting/crane operations permit'),
('Radiation', 'RADIATION', 'Radiation work permit');

CREATE TABLE permits (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    serial_no VARCHAR(100) NOT NULL UNIQUE,
    permit_type_id BIGINT UNSIGNED NOT NULL,
    type_of_job VARCHAR(100) NULL,
    from_department VARCHAR(150) NULL,
    to_department VARCHAR(150) NULL,
    section VARCHAR(150) NULL,
    plant VARCHAR(150) NULL,
    project VARCHAR(150) NULL,
    location VARCHAR(255) NULL,
    equipment VARCHAR(255) NULL,
    other_permit_number VARCHAR(100) NULL,
    notification_number VARCHAR(100) NULL,
    job_type VARCHAR(255) NULL,
    job_description TEXT NULL,
    job_to_be_carried_out_by VARCHAR(255) NULL,
    department_or_contractor VARCHAR(255) NULL,
    manpower_count INT UNSIGNED NULL,
    company_supervisor VARCHAR(150) NULL,
    supervisor_user_id VARCHAR(64) NULL,
    permittee_name VARCHAR(150) NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'DRAFT',
    valid_from DATETIME NULL,
    valid_to DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    submitted_at DATETIME NULL,
    activated_at DATETIME NULL,
    closed_at DATETIME NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    extra_data JSON NULL,
    CONSTRAINT fk_permits_type
        FOREIGN KEY (permit_type_id) REFERENCES permit_types(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_permits_supervisor
        FOREIGN KEY (supervisor_user_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    INDEX idx_permits_type (permit_type_id),
    INDEX idx_permits_status (status),
    INDEX idx_permits_plant (plant),
    INDEX idx_permits_location (location),
    INDEX idx_permits_dates (valid_from, valid_to)
) ENGINE=InnoDB;

CREATE TABLE workers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_code VARCHAR(100) NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    company VARCHAR(255) NULL,
    role_title VARCHAR(150) NULL,
    phone VARCHAR(50) NULL,
    training_valid_until DATE NULL,
    authorization_valid_until DATE NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE permit_workers (
    permit_id BIGINT UNSIGNED NOT NULL,
    worker_id BIGINT UNSIGNED NOT NULL,
    worker_role VARCHAR(100) NULL,
    signed_at DATETIME NULL,
    notes TEXT NULL,
    PRIMARY KEY (permit_id, worker_id),
    CONSTRAINT fk_permit_workers_permit
        FOREIGN KEY (permit_id) REFERENCES permits(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_permit_workers_worker
        FOREIGN KEY (worker_id) REFERENCES workers(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

-- =========================================================
-- CHECKLIST ENGINE
-- =========================================================

CREATE TABLE checklist_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    permit_type_id BIGINT UNSIGNED NOT NULL,
    item_code VARCHAR(100) NULL,
    section_name VARCHAR(255) NULL,
    question TEXT NOT NULL,
    response_type VARCHAR(30) NOT NULL DEFAULT 'YES_NO_NA',
    is_mandatory BOOLEAN NOT NULL DEFAULT TRUE,
    sort_order INT NOT NULL DEFAULT 0,
    source_reference VARCHAR(255) NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    metadata JSON NULL,
    CONSTRAINT fk_checklist_items_type
        FOREIGN KEY (permit_type_id) REFERENCES permit_types(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    UNIQUE KEY uq_checklist_item_code (permit_type_id, item_code),
    INDEX idx_checklist_type_order (permit_type_id, sort_order)
) ENGINE=InnoDB;

CREATE TABLE checklist_responses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    permit_id BIGINT UNSIGNED NOT NULL,
    checklist_item_id BIGINT UNSIGNED NOT NULL,
    response VARCHAR(30) NOT NULL,
    remarks TEXT NULL,
    responded_by VARCHAR(64) NULL,
    responded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    evidence_document_id BIGINT UNSIGNED NULL,
    CONSTRAINT fk_checklist_response_permit
        FOREIGN KEY (permit_id) REFERENCES permits(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_checklist_response_item
        FOREIGN KEY (checklist_item_id) REFERENCES checklist_items(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_checklist_response_user
        FOREIGN KEY (responded_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    INDEX idx_checklist_response_permit (permit_id),
    INDEX idx_checklist_response_item (checklist_item_id)
) ENGINE=InnoDB;

-- =========================================================
-- GAS TESTING
-- =========================================================

CREATE TABLE gas_tests (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    permit_id BIGINT UNSIGNED NOT NULL,
    tested_at DATETIME NOT NULL,
    tested_by VARCHAR(64) NULL,
    flammable_lel DECIMAL(8,3) NULL,
    oxygen_percent DECIMAL(6,3) NULL,
    toxic_gas_ppm DECIMAL(12,3) NULL,
    instrument VARCHAR(255) NULL,
    calibration_valid_until DATE NULL,
    result VARCHAR(30) NULL,
    remarks TEXT NULL,
    signature_name VARCHAR(150) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_gas_tests_permit
        FOREIGN KEY (permit_id) REFERENCES permits(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_gas_tests_user
        FOREIGN KEY (tested_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    INDEX idx_gas_tests_permit_time (permit_id, tested_at)
) ENGINE=InnoDB;

-- =========================================================
-- JSA / AI
-- =========================================================

CREATE TABLE jsa_records (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    permit_id BIGINT UNSIGNED NOT NULL,
    jsa_number VARCHAR(100) NOT NULL UNIQUE,
    job_breakdown JSON NULL,
    hazards JSON NULL,
    consequences JSON NULL,
    controls JSON NULL,
    responsible_person JSON NULL,
    manpower JSON NULL,
    ppe JSON NULL,
    tools_equipment JSON NULL,
    monitoring JSON NULL,
    emergency_procedure JSON NULL,
    special_precautions JSON NULL,
    ai_input JSON NULL,
    ai_model VARCHAR(150) NULL,
    ai_model_version VARCHAR(100) NULL,
    ai_output JSON NULL,
    review_status VARCHAR(50) NOT NULL DEFAULT 'PENDING',
    reviewed_by VARCHAR(64) NULL,
    reviewed_at DATETIME NULL,
    review_remarks TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_jsa_permit
        FOREIGN KEY (permit_id) REFERENCES permits(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_jsa_reviewer
        FOREIGN KEY (reviewed_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    INDEX idx_jsa_permit (permit_id),
    INDEX idx_jsa_review (review_status)
) ENGINE=InnoDB;

-- =========================================================
-- PPE
-- =========================================================

CREATE TABLE ppe_matrix (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    description TEXT NULL,
    permit_type_id BIGINT UNSIGNED NULL,
    configuration JSON NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    CONSTRAINT fk_ppe_matrix_type
        FOREIGN KEY (permit_type_id) REFERENCES permit_types(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE ppe_requirements (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ppe_matrix_id BIGINT UNSIGNED NOT NULL,
    item_name VARCHAR(150) NOT NULL,
    requirement_level VARCHAR(50) NULL,
    condition_text TEXT NULL,
    source_reference VARCHAR(255) NULL,
    is_mandatory BOOLEAN NOT NULL DEFAULT TRUE,
    CONSTRAINT fk_ppe_requirement_matrix
        FOREIGN KEY (ppe_matrix_id) REFERENCES ppe_matrix(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

-- =========================================================
-- LOTO
-- =========================================================

CREATE TABLE loto_records (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    permit_id BIGINT UNSIGNED NOT NULL,
    record_number VARCHAR(100) NULL,
    equipment VARCHAR(255) NULL,
    isolation_method TEXT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'OPEN',
    applied_by VARCHAR(64) NULL,
    applied_at DATETIME NULL,
    removed_by VARCHAR(64) NULL,
    removed_at DATETIME NULL,
    verification_notes TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_loto_permit
        FOREIGN KEY (permit_id) REFERENCES permits(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_loto_applied_by
        FOREIGN KEY (applied_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_loto_removed_by
        FOREIGN KEY (removed_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    INDEX idx_loto_permit (permit_id),
    INDEX idx_loto_status (status)
) ENGINE=InnoDB;

CREATE TABLE isolation_points (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    loto_record_id BIGINT UNSIGNED NOT NULL,
    equipment_tag VARCHAR(150) NULL,
    isolation_type VARCHAR(100) NULL,
    location VARCHAR(255) NULL,
    tag_number VARCHAR(100) NULL,
    blind_number VARCHAR(100) NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'ISOLATED',
    verified_by VARCHAR(64) NULL,
    verified_at DATETIME NULL,
    notes TEXT NULL,
    CONSTRAINT fk_isolation_loto
        FOREIGN KEY (loto_record_id) REFERENCES loto_records(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_isolation_verifier
        FOREIGN KEY (verified_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

-- =========================================================
-- DOCUMENTS
-- =========================================================

CREATE TABLE documents (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    permit_id BIGINT UNSIGNED NULL,
    uploaded_by VARCHAR(64) NULL,
    document_type VARCHAR(100) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    storage_path VARCHAR(1000) NOT NULL,
    mime_type VARCHAR(150) NULL,
    file_size BIGINT UNSIGNED NULL,
    checksum VARCHAR(128) NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'ACTIVE',
    uploaded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    metadata JSON NULL,
    CONSTRAINT fk_documents_permit
        FOREIGN KEY (permit_id) REFERENCES permits(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_documents_user
        FOREIGN KEY (uploaded_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    INDEX idx_documents_permit (permit_id),
    INDEX idx_documents_type (document_type)
) ENGINE=InnoDB;

ALTER TABLE checklist_responses
    ADD CONSTRAINT fk_checklist_response_document
    FOREIGN KEY (evidence_document_id) REFERENCES documents(id)
    ON UPDATE CASCADE ON DELETE SET NULL;

-- =========================================================
-- APPROVALS / LIFECYCLE
-- =========================================================

CREATE TABLE approvals (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    permit_id BIGINT UNSIGNED NOT NULL,
    approval_stage VARCHAR(80) NOT NULL,
    decision VARCHAR(50) NOT NULL,
    approved_by VARCHAR(64) NULL,
    approved_at DATETIME NULL,
    remarks TEXT NULL,
    signature_name VARCHAR(150) NULL,
    signature_data LONGTEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_approvals_permit
        FOREIGN KEY (permit_id) REFERENCES permits(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_approvals_user
        FOREIGN KEY (approved_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    INDEX idx_approvals_permit (permit_id),
    INDEX idx_approvals_stage (approval_stage)
) ENGINE=InnoDB;

CREATE TABLE renewals (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    permit_id BIGINT UNSIGNED NOT NULL,
    previous_valid_from DATETIME NULL,
    previous_valid_to DATETIME NULL,
    new_valid_from DATETIME NULL,
    new_valid_to DATETIME NULL,
    requested_by VARCHAR(64) NULL,
    requested_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    decision VARCHAR(50) NULL,
    decided_by VARCHAR(64) NULL,
    decided_at DATETIME NULL,
    remarks TEXT NULL,
    CONSTRAINT fk_renewals_permit
        FOREIGN KEY (permit_id) REFERENCES permits(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_renewals_requester
        FOREIGN KEY (requested_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_renewals_decider
        FOREIGN KEY (decided_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    INDEX idx_renewals_permit (permit_id)
) ENGINE=InnoDB;

CREATE TABLE extensions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    permit_id BIGINT UNSIGNED NOT NULL,
    requested_from DATETIME NULL,
    requested_to DATETIME NULL,
    reason TEXT NULL,
    requested_by VARCHAR(64) NULL,
    requested_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    decision VARCHAR(50) NULL,
    decided_by VARCHAR(64) NULL,
    decided_at DATETIME NULL,
    remarks TEXT NULL,
    CONSTRAINT fk_extensions_permit
        FOREIGN KEY (permit_id) REFERENCES permits(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_extensions_requester
        FOREIGN KEY (requested_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_extensions_decider
        FOREIGN KEY (decided_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    INDEX idx_extensions_permit (permit_id)
) ENGINE=InnoDB;

CREATE TABLE closures (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    permit_id BIGINT UNSIGNED NOT NULL,
    closed_by VARCHAR(64) NULL,
    closed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    closure_status VARCHAR(50) NOT NULL,
    housekeeping_confirmed BOOLEAN NOT NULL DEFAULT FALSE,
    isolation_restored BOOLEAN NOT NULL DEFAULT FALSE,
    work_completed BOOLEAN NOT NULL DEFAULT FALSE,
    remarks TEXT NULL,
    signature_name VARCHAR(150) NULL,
    CONSTRAINT fk_closures_permit
        FOREIGN KEY (permit_id) REFERENCES permits(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_closures_user
        FOREIGN KEY (closed_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    INDEX idx_closures_permit (permit_id)
) ENGINE=InnoDB;

CREATE TABLE emergency_procedures (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    permit_id BIGINT UNSIGNED NULL,
    permit_type_id BIGINT UNSIGNED NULL,
    title VARCHAR(255) NOT NULL,
    procedure_text LONGTEXT NOT NULL,
    source_reference VARCHAR(255) NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_emergency_permit
        FOREIGN KEY (permit_id) REFERENCES permits(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_emergency_type
        FOREIGN KEY (permit_type_id) REFERENCES permit_types(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE handover_records (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    permit_id BIGINT UNSIGNED NOT NULL,
    handover_type VARCHAR(80) NOT NULL,
    from_user_id VARCHAR(64) NULL,
    to_user_id VARCHAR(64) NULL,
    handover_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    checklist JSON NULL,
    remarks TEXT NULL,
    signature_name VARCHAR(150) NULL,
    CONSTRAINT fk_handover_permit
        FOREIGN KEY (permit_id) REFERENCES permits(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_handover_from
        FOREIGN KEY (from_user_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_handover_to
        FOREIGN KEY (to_user_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    INDEX idx_handover_permit (permit_id)
) ENGINE=InnoDB;

-- =========================================================
-- AUDIT / SYSTEM CONFIGURATION
-- =========================================================

CREATE TABLE audit_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(64) NULL,
    entity_type VARCHAR(100) NOT NULL,
    entity_id VARCHAR(100) NULL,
    action VARCHAR(100) NOT NULL,
    old_data JSON NULL,
    new_data JSON NULL,
    ip_address VARCHAR(45) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_audit_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    INDEX idx_audit_entity (entity_type, entity_id),
    INDEX idx_audit_user (user_id),
    INDEX idx_audit_created (created_at)
) ENGINE=InnoDB;

CREATE TABLE plant_config (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    config_key VARCHAR(150) NOT NULL UNIQUE,
    config_value JSON NOT NULL,
    description TEXT NULL,
    updated_by VARCHAR(64) NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_plant_config_user
        FOREIGN KEY (updated_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE webhooks (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    endpoint_url VARCHAR(1000) NOT NULL,
    event_name VARCHAR(100) NOT NULL,
    secret_hash VARCHAR(255) NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    last_triggered_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE retention_policy (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    entity_name VARCHAR(100) NOT NULL UNIQUE,
    retention_days INT UNSIGNED NOT NULL,
    action_after_retention VARCHAR(50) NOT NULL DEFAULT 'ARCHIVE',
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =========================================================
-- BASIC SEED CONFIGURATION
-- =========================================================

INSERT INTO retention_policy (entity_name, retention_days, action_after_retention) VALUES
('permits', 90, 'ARCHIVE'),
('audit_events', 365, 'ARCHIVE'),
('auth_logs', 365, 'ARCHIVE'),
('sessions', 30, 'DELETE');

-- NOTE:
-- Do NOT insert the plaintext passwords from the supplied users.json.
-- Application code should create password_hash values using password_hash()
-- / PASSWORD_DEFAULT (PHP) or the equivalent secure password hashing library.

-- End of schema.
