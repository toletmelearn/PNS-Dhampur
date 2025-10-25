-- Create fees table
CREATE TABLE IF NOT EXISTS fees (
    id bigint unsigned NOT NULL AUTO_INCREMENT,
    student_id bigint unsigned NOT NULL,
    fee_type varchar(191) NOT NULL,
    amount decimal(10,2) NOT NULL,
    due_date date NOT NULL,
    academic_year varchar(191) NOT NULL,
    month varchar(191) NOT NULL,
    late_fee decimal(10,2) DEFAULT 0,
    discount decimal(10,2) DEFAULT 0,
    paid_amount decimal(10,2) DEFAULT 0,
    paid_date date NULL,
    status enum('unpaid','partial','paid','overdue') DEFAULT 'unpaid',
    payment_mode varchar(191) NULL,
    transaction_id varchar(191) NULL,
    receipt_no varchar(191) NULL,
    remarks text NULL,
    created_at timestamp NULL,
    updated_at timestamp NULL,
    deleted_at timestamp NULL,
    PRIMARY KEY (id),
    KEY fees_student_id_foreign (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create fee_payments table
CREATE TABLE IF NOT EXISTS fee_payments (
    id bigint unsigned NOT NULL AUTO_INCREMENT,
    fee_id bigint unsigned NOT NULL,
    amount_paid decimal(10,2) NOT NULL,
    payment_date timestamp NOT NULL,
    payment_mode enum('cash','online','card','cheque','bank_transfer') NOT NULL,
    transaction_id varchar(191) NULL,
    receipt_no varchar(191) NOT NULL,
    remarks text NULL,
    created_at timestamp NULL,
    updated_at timestamp NULL,
    deleted_at timestamp NULL,
    PRIMARY KEY (id),
    KEY fee_payments_fee_id_foreign (fee_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;