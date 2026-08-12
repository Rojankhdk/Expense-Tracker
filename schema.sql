-- =====================================================================
-- SpendWise / Expense Tracker — Database Schema
-- Generated to match the ER diagram (7 entities, Chen notation)
--
-- Two deliberate additions beyond the raw ER diagram (documented so
-- you can explain them if asked in your defense):
--   1. monthly_budget.month_year  — the ER's "Monthly Budget" entity had
--      no month/period attribute, so budgets couldn't be tracked per
--      month without it.
--   2. transaction_details.type / .amount — the ER's "Transaction
--      Details" entity had no type or amount attribute, so it couldn't
--      hold real transaction data on its own. These two columns were
--      added, and the table is kept in sync with expenses/income
--      automatically via triggers (see below) — the PHP code never
--      writes to it directly.
-- =====================================================================

DROP DATABASE IF EXISTS ExpenseTracker;
CREATE DATABASE ExpenseTracker;
USE ExpenseTracker;

-- ---------------------------------------------------------------------
-- ADMIN  (Admin_id, Username, Password)  -- "Manages" User (1:M)
-- Created before USER so USER can carry a real FK back to it.
-- ---------------------------------------------------------------------
CREATE TABLE admin (
    admin_id    INT AUTO_INCREMENT PRIMARY KEY,
    username    VARCHAR(50)  NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL
);

-- Seed the one admin account the app currently logs in as (see login.php).
-- Password is 'Password123', hashed with bcrypt so it matches PHP's
-- password_hash()/password_verify() format.
INSERT INTO admin (admin_id, username, password)
VALUES (1, 'admin', '$2b$10$wZpIft6BcRLSEDrH4Qn7DuqLFQn3H1SD4FRuNHiYR5KSleh4nRUUK');

-- ---------------------------------------------------------------------
-- USER  (User_id, Username, Password, Email)
-- admin_id added so the "Manages" relationship (Admin 1 : M User) is a
-- real FK, not just implied — every user is managed by the seeded admin.
-- ---------------------------------------------------------------------
CREATE TABLE user (
    user_id     INT AUTO_INCREMENT PRIMARY KEY,
    admin_id    INT NOT NULL DEFAULT 1,
    username    VARCHAR(50)  NOT NULL,
    email       VARCHAR(100) NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,
    FOREIGN KEY (admin_id) REFERENCES admin(admin_id) ON DELETE RESTRICT
);

-- ---------------------------------------------------------------------
-- CATEGORY  (Category_id, User_id, Category_name)  -- belongs to User
-- ---------------------------------------------------------------------
CREATE TABLE category (
    category_id     INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL,
    category_name   VARCHAR(50) NOT NULL,
    FOREIGN KEY (user_id) REFERENCES user(user_id) ON DELETE CASCADE,
    UNIQUE KEY uniq_user_category (user_id, category_name)
);

-- ---------------------------------------------------------------------
-- INCOME  (Income_id, User_id, Amount, Source, Date)
-- User "Records" Income (1:M)
-- ---------------------------------------------------------------------
CREATE TABLE income (
    income_id   INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    amount      DECIMAL(10,2) NOT NULL,
    source      VARCHAR(100) NOT NULL,
    income_date DATE NOT NULL DEFAULT (CURRENT_DATE),
    FOREIGN KEY (user_id) REFERENCES user(user_id) ON DELETE CASCADE
);

-- ---------------------------------------------------------------------
-- EXPENSES  (Expense_id, User_id, Category_id, Amount, Description, Expense_date)
-- User "Records" Expenses (1:M) / Category "Has" Expenses (1:M)
-- ---------------------------------------------------------------------
CREATE TABLE expenses (
    expense_id   INT AUTO_INCREMENT PRIMARY KEY,
    user_id      INT NOT NULL,
    category_id  INT NOT NULL,
    amount       DECIMAL(10,2) NOT NULL,
    description  VARCHAR(255),
    expense_date DATE NOT NULL DEFAULT (CURRENT_DATE),
    FOREIGN KEY (user_id) REFERENCES user(user_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES category(category_id) ON DELETE RESTRICT
);

-- ---------------------------------------------------------------------
-- MONTHLY BUDGET  (Budget_id, User_id, Category_id, Amount [+ month_year])
-- User "Sets" Monthly Budget (1:M) / Category "Has" Monthly Budget (1:M)
-- ---------------------------------------------------------------------
CREATE TABLE monthly_budget (
    budget_id    INT AUTO_INCREMENT PRIMARY KEY,
    user_id      INT NOT NULL,
    category_id  INT NOT NULL,
    amount       DECIMAL(10,2) NOT NULL,
    month_year   CHAR(7) NOT NULL,           -- e.g. '2026-08'  (added, see header note)
    FOREIGN KEY (user_id) REFERENCES user(user_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES category(category_id) ON DELETE CASCADE,
    UNIQUE KEY uniq_budget (user_id, category_id, month_year)
);

-- ---------------------------------------------------------------------
-- TRANSACTION DETAILS  (Trans_id, User_id, Description, Expense_date)
-- User "Gets" Transaction Details (real FK to user, visible in the
-- phpMyAdmin relation/designer view).
--
-- This is kept in sync with expenses/income automatically via triggers
-- below, so the PHP code never has to write to it directly — it only
-- inserts into expenses/income as before, and transaction_details
-- mirrors those rows for a unified history feed.
-- ---------------------------------------------------------------------
CREATE TABLE transaction_details (
    trans_id     VARCHAR(20) PRIMARY KEY,   -- 'E12' for expense id 12, 'I7' for income id 7
    user_id      INT NOT NULL,
    type         ENUM('income','expense') NOT NULL,
    amount       DECIMAL(10,2) NOT NULL,
    description  VARCHAR(255),
    trans_date   DATE NOT NULL,
    FOREIGN KEY (user_id) REFERENCES user(user_id) ON DELETE CASCADE
);

DELIMITER $$

CREATE TRIGGER trg_expenses_insert
AFTER INSERT ON expenses
FOR EACH ROW
BEGIN
    INSERT INTO transaction_details (trans_id, user_id, type, amount, description, trans_date)
    VALUES (CONCAT('E', NEW.expense_id), NEW.user_id, 'expense', NEW.amount, NEW.description, NEW.expense_date);
END$$

CREATE TRIGGER trg_expenses_update
AFTER UPDATE ON expenses
FOR EACH ROW
BEGIN
    UPDATE transaction_details
    SET user_id = NEW.user_id, amount = NEW.amount, description = NEW.description, trans_date = NEW.expense_date
    WHERE trans_id = CONCAT('E', NEW.expense_id);
END$$

CREATE TRIGGER trg_expenses_delete
AFTER DELETE ON expenses
FOR EACH ROW
BEGIN
    DELETE FROM transaction_details WHERE trans_id = CONCAT('E', OLD.expense_id);
END$$

CREATE TRIGGER trg_income_insert
AFTER INSERT ON income
FOR EACH ROW
BEGIN
    INSERT INTO transaction_details (trans_id, user_id, type, amount, description, trans_date)
    VALUES (CONCAT('I', NEW.income_id), NEW.user_id, 'income', NEW.amount, NEW.source, NEW.income_date);
END$$

CREATE TRIGGER trg_income_update
AFTER UPDATE ON income
FOR EACH ROW
BEGIN
    UPDATE transaction_details
    SET user_id = NEW.user_id, amount = NEW.amount, description = NEW.source, trans_date = NEW.income_date
    WHERE trans_id = CONCAT('I', NEW.income_id);
END$$

CREATE TRIGGER trg_income_delete
AFTER DELETE ON income
FOR EACH ROW
BEGIN
    DELETE FROM transaction_details WHERE trans_id = CONCAT('I', OLD.income_id);
END$$

DELIMITER ;

-- NOTE: login.php still authenticates the admin via hardcoded
-- constants (see comment in that file) rather than checking this
-- table's password hash — the FK/seed above exists so the ER's
-- Admin–User relationship is real in the schema. Say the word if you
-- want login.php fully wired to check against this table instead.
