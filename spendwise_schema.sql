-- =====================================================
-- SpendWise Database Schema
-- Matches: db.php, login.php, register.php,
--          dashboard.php, budget.php, transaction.php
-- =====================================================

CREATE DATABASE IF NOT EXISTS ExpenseTracker;
USE ExpenseTracker;

-- -----------------------------------------------------
-- USER
-- Used by: login.php, register.php, admin_dashboard.php
-- -----------------------------------------------------
CREATE TABLE user (
    user_id     INT AUTO_INCREMENT PRIMARY KEY,
    username    VARCHAR(100) NOT NULL,
    email       VARCHAR(150) NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,          -- stored with password_hash()
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Note: the admin account (admin@spendwise.com) is hardcoded in login.php,
-- not stored in this table, so no separate admin table is needed here.

-- -----------------------------------------------------
-- CATEGORY
-- Used by: dashboard.php (add_category, expense dropdown),
--          budget.php (add_category, category list/dropdown)
-- -----------------------------------------------------
CREATE TABLE category (
    category_id     INT AUTO_INCREMENT PRIMARY KEY,
    user_id          INT NOT NULL,
    category_name    VARCHAR(100) NOT NULL,
    UNIQUE KEY uq_user_category (user_id, category_name),  -- makes INSERT IGNORE work
    FOREIGN KEY (user_id) REFERENCES user(user_id) ON DELETE CASCADE
);

-- -----------------------------------------------------
-- INCOME
-- Used by: dashboard.php (add income, monthly total)
-- -----------------------------------------------------
CREATE TABLE income (
    income_id     INT AUTO_INCREMENT PRIMARY KEY,
    user_id       INT NOT NULL,
    amount        DECIMAL(10,2) NOT NULL,
    source        VARCHAR(150) NOT NULL,
    income_date   DATE NOT NULL DEFAULT (CURRENT_DATE),
    FOREIGN KEY (user_id) REFERENCES user(user_id) ON DELETE CASCADE
);

-- -----------------------------------------------------
-- EXPENSES
-- Used by: dashboard.php (add expense, budget overview),
--          transaction.php (budget status)
-- -----------------------------------------------------
CREATE TABLE expenses (
    expense_id     INT AUTO_INCREMENT PRIMARY KEY,
    user_id        INT NOT NULL,
    category_id    INT NOT NULL,
    amount         DECIMAL(10,2) NOT NULL,
    description    VARCHAR(255),
    expense_date   DATE NOT NULL DEFAULT (CURRENT_DATE),
    FOREIGN KEY (user_id) REFERENCES user(user_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES category(category_id) ON DELETE CASCADE
);

-- -----------------------------------------------------
-- MONTHLY_BUDGET
-- Used by: budget.php (set_budget with ON DUPLICATE KEY UPDATE),
--          dashboard.php / transaction.php (budget overview join)
-- -----------------------------------------------------
CREATE TABLE monthly_budget (
    budget_id      INT AUTO_INCREMENT PRIMARY KEY,
    user_id        INT NOT NULL,
    category_id    INT NOT NULL,
    amount         DECIMAL(10,2) NOT NULL,
    month_year     CHAR(7) NOT NULL,  -- format: 'YYYY-MM'
    UNIQUE KEY uq_budget (user_id, category_id, month_year),  -- needed for ON DUPLICATE KEY UPDATE
    FOREIGN KEY (user_id) REFERENCES user(user_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES category(category_id) ON DELETE CASCADE
);

-- -----------------------------------------------------
-- TRANSACTION_DETAILS (view)
-- Used by: transaction.php -> SELECT * FROM transaction_details
--          WHERE user_id = ? ORDER BY trans_date DESC
-- Combines income + expenses into one unified log.
-- -----------------------------------------------------
CREATE VIEW transaction_details AS
    SELECT
        user_id,
        income_id           AS ref_id,
        'income'            AS type,
        amount,
        source              AS description,
        income_date         AS trans_date
    FROM income
    UNION ALL
    SELECT
        user_id,
        expense_id          AS ref_id,
        'expense'           AS type,
        amount,
        description,
        expense_date        AS trans_date
    FROM expenses;
