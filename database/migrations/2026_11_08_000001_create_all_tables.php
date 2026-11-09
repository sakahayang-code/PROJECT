<?php

use App\Core\Database;

return new class {
    public function up(): void
    {
        $db = Database::getInstance()->getConnection();

        // A. Perusahaan, cabang, dan pengguna
        $db->exec("
            CREATE TABLE IF NOT EXISTS companies (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                code TEXT NOT NULL UNIQUE,
                name TEXT NOT NULL,
                tagline TEXT,
                email TEXT,
                phone TEXT,
                address TEXT,
                logo TEXT,
                currency TEXT DEFAULT 'IDR',
                settings TEXT,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL
            );

            CREATE TABLE IF NOT EXISTS branches (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                company_id INTEGER NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
                code TEXT NOT NULL UNIQUE,
                name TEXT NOT NULL,
                phone TEXT,
                email TEXT,
                address TEXT,
                is_main INTEGER DEFAULT 0,
                is_active INTEGER DEFAULT 1,
                settings TEXT,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL
            );
            CREATE INDEX IF NOT EXISTS idx_branches_company ON branches(company_id);

            CREATE TABLE IF NOT EXISTS roles (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                company_id INTEGER REFERENCES companies(id) ON DELETE CASCADE,
                name TEXT NOT NULL,
                slug TEXT NOT NULL UNIQUE,
                description TEXT,
                is_system INTEGER DEFAULT 0,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL
            );

            CREATE TABLE IF NOT EXISTS permissions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                slug TEXT NOT NULL UNIQUE,
                group_name TEXT NOT NULL,
                description TEXT,
                created_at TEXT NOT NULL
            );

            CREATE TABLE IF NOT EXISTS role_permissions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                role_id INTEGER NOT NULL REFERENCES roles(id) ON DELETE CASCADE,
                permission_id INTEGER NOT NULL REFERENCES permissions(id) ON DELETE CASCADE,
                created_at TEXT NOT NULL,
                UNIQUE(role_id, permission_id)
            );
            CREATE INDEX IF NOT EXISTS idx_role_perm ON role_permissions(role_id, permission_id);

            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                company_id INTEGER NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
                branch_id INTEGER REFERENCES branches(id) ON DELETE SET NULL,
                username TEXT NOT NULL UNIQUE,
                name TEXT NOT NULL,
                email TEXT,
                phone TEXT,
                password_hash TEXT NOT NULL,
                role_id INTEGER NOT NULL REFERENCES roles(id),
                is_active INTEGER DEFAULT 1,
                avatar TEXT,
                last_login_at TEXT,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL
            );
            CREATE INDEX IF NOT EXISTS idx_users_branch ON users(branch_id);
            CREATE INDEX IF NOT EXISTS idx_users_role ON users(role_id);

            CREATE TABLE IF NOT EXISTS user_branch_access (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
                branch_id INTEGER NOT NULL REFERENCES branches(id) ON DELETE CASCADE,
                is_default INTEGER DEFAULT 0,
                created_at TEXT NOT NULL,
                UNIQUE(user_id, branch_id)
            );

            CREATE TABLE IF NOT EXISTS employees (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                company_id INTEGER NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
                branch_id INTEGER NOT NULL REFERENCES branches(id) ON DELETE CASCADE,
                user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
                nik TEXT,
                name TEXT NOT NULL,
                phone TEXT,
                email TEXT,
                position TEXT NOT NULL,
                base_salary INTEGER DEFAULT 0,
                status TEXT DEFAULT 'active' CHECK(status IN ('active', 'inactive', 'leave')),
                join_date TEXT,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL
            );
            CREATE INDEX IF NOT EXISTS idx_emp_branch ON employees(branch_id);

            CREATE TABLE IF NOT EXISTS employee_shifts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                branch_id INTEGER NOT NULL REFERENCES branches(id) ON DELETE CASCADE,
                name TEXT NOT NULL,
                start_time TEXT NOT NULL,
                end_time TEXT NOT NULL,
                created_at TEXT NOT NULL
            );

            CREATE TABLE IF NOT EXISTS attendance (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                company_id INTEGER NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
                branch_id INTEGER NOT NULL REFERENCES branches(id) ON DELETE CASCADE,
                employee_id INTEGER NOT NULL REFERENCES employees(id) ON DELETE CASCADE,
                date TEXT NOT NULL,
                clock_in TEXT,
                clock_out TEXT,
                status TEXT DEFAULT 'present' CHECK(status IN ('present', 'late', 'absent', 'leave')),
                notes TEXT,
                created_at TEXT NOT NULL
            );
            CREATE INDEX IF NOT EXISTS idx_attendance_date ON attendance(branch_id, date);

            CREATE TABLE IF NOT EXISTS activity_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                company_id INTEGER REFERENCES companies(id) ON DELETE CASCADE,
                branch_id INTEGER REFERENCES branches(id) ON DELETE SET NULL,
                user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
                action TEXT NOT NULL,
                entity_type TEXT NOT NULL,
                entity_id INTEGER,
                description TEXT,
                ip_address TEXT,
                user_agent TEXT,
                created_at TEXT NOT NULL
            );
            CREATE INDEX IF NOT EXISTS idx_activity_created ON activity_logs(created_at);

            CREATE TABLE IF NOT EXISTS login_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
                username TEXT,
                ip_address TEXT,
                user_agent TEXT,
                status TEXT NOT NULL,
                failure_reason TEXT,
                created_at TEXT NOT NULL
            );
        ");

        // B. Pelanggan dan membership
        $db->exec("
            CREATE TABLE IF NOT EXISTS membership_tiers (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                company_id INTEGER NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
                name TEXT NOT NULL,
                slug TEXT NOT NULL,
                min_spend INTEGER DEFAULT 0,
                min_points INTEGER DEFAULT 0,
                discount_percent REAL DEFAULT 0,
                points_multiplier REAL DEFAULT 1.0,
                description TEXT,
                badge_color TEXT DEFAULT '#13AFA8',
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL
            );

            CREATE TABLE IF NOT EXISTS customers (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                company_id INTEGER NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
                favorite_branch_id INTEGER REFERENCES branches(id) ON DELETE SET NULL,
                code TEXT NOT NULL UNIQUE,
                name TEXT NOT NULL,
                whatsapp TEXT NOT NULL,
                email TEXT,
                address TEXT,
                birth_date TEXT,
                join_date TEXT NOT NULL,
                status TEXT DEFAULT 'active' CHECK(status IN ('active', 'inactive', 'blocked')),
                deposit_balance INTEGER DEFAULT 0,
                points_balance INTEGER DEFAULT 0,
                current_tier_id INTEGER REFERENCES membership_tiers(id) ON DELETE SET NULL,
                notes TEXT,
                allow_marketing INTEGER DEFAULT 1,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL
            );
            CREATE INDEX IF NOT EXISTS idx_customers_phone ON customers(whatsapp);
            CREATE INDEX IF NOT EXISTS idx_customers_name ON customers(name);

            CREATE TABLE IF NOT EXISTS customer_addresses (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                customer_id INTEGER NOT NULL REFERENCES customers(id) ON DELETE CASCADE,
                label TEXT NOT NULL,
                recipient_name TEXT NOT NULL,
                phone TEXT NOT NULL,
                address TEXT NOT NULL,
                notes TEXT,
                is_default INTEGER DEFAULT 0,
                created_at TEXT NOT NULL
            );

            CREATE TABLE IF NOT EXISTS customer_accounts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                customer_id INTEGER NOT NULL UNIQUE REFERENCES customers(id) ON DELETE CASCADE,
                username TEXT NOT NULL UNIQUE,
                password_hash TEXT NOT NULL,
                is_active INTEGER DEFAULT 1,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL
            );

            CREATE TABLE IF NOT EXISTS customer_memberships (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                customer_id INTEGER NOT NULL REFERENCES customers(id) ON DELETE CASCADE,
                tier_id INTEGER NOT NULL REFERENCES membership_tiers(id) ON DELETE CASCADE,
                valid_from TEXT NOT NULL,
                valid_until TEXT,
                created_at TEXT NOT NULL
            );

            CREATE TABLE IF NOT EXISTS loyalty_transactions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                company_id INTEGER NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
                customer_id INTEGER NOT NULL REFERENCES customers(id) ON DELETE CASCADE,
                order_id INTEGER,
                type TEXT NOT NULL CHECK(type IN ('earn', 'redeem', 'adjust', 'expire')),
                points INTEGER NOT NULL,
                balance_after INTEGER NOT NULL,
                description TEXT,
                reference_no TEXT,
                created_at TEXT NOT NULL
            );
            CREATE INDEX IF NOT EXISTS idx_loyalty_customer ON loyalty_transactions(customer_id);

            CREATE TABLE IF NOT EXISTS rewards (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                company_id INTEGER NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
                name TEXT NOT NULL,
                points_cost INTEGER NOT NULL,
                description TEXT,
                is_active INTEGER DEFAULT 1,
                stock INTEGER DEFAULT 0,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL
            );

            CREATE TABLE IF NOT EXISTS vouchers (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                company_id INTEGER NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
                code TEXT NOT NULL UNIQUE,
                name TEXT NOT NULL,
                type TEXT NOT NULL CHECK(type IN ('percent', 'fixed')),
                value INTEGER NOT NULL,
                min_spend INTEGER DEFAULT 0,
                max_discount INTEGER DEFAULT 0,
                quota INTEGER DEFAULT 0,
                used_count INTEGER DEFAULT 0,
                start_date TEXT NOT NULL,
                end_date TEXT NOT NULL,
                is_active INTEGER DEFAULT 1,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL
            );

            CREATE TABLE IF NOT EXISTS voucher_usages (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                voucher_id INTEGER NOT NULL REFERENCES vouchers(id) ON DELETE CASCADE,
                order_id INTEGER NOT NULL,
                customer_id INTEGER NOT NULL REFERENCES customers(id) ON DELETE CASCADE,
                discount_amount INTEGER NOT NULL,
                used_at TEXT NOT NULL
            );

            CREATE TABLE IF NOT EXISTS referrals (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                company_id INTEGER NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
                referrer_customer_id INTEGER NOT NULL REFERENCES customers(id) ON DELETE CASCADE,
                referred_customer_id INTEGER NOT NULL REFERENCES customers(id) ON DELETE CASCADE,
                reward_points INTEGER DEFAULT 0,
                status TEXT DEFAULT 'pending' CHECK(status IN ('pending', 'rewarded', 'cancelled')),
                created_at TEXT NOT NULL
            );
        ");

        // C. Layanan dan harga
        $db->exec("
            CREATE TABLE IF NOT EXISTS service_categories (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                company_id INTEGER NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
                name TEXT NOT NULL,
                slug TEXT NOT NULL,
                icon TEXT,
                description TEXT,
                sort_order INTEGER DEFAULT 0,
                is_active INTEGER DEFAULT 1,
                created_at TEXT NOT NULL
            );

            CREATE TABLE IF NOT EXISTS services (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                company_id INTEGER NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
                category_id INTEGER NOT NULL REFERENCES service_categories(id) ON DELETE CASCADE,
                code TEXT NOT NULL UNIQUE,
                name TEXT NOT NULL,
                unit_type TEXT NOT NULL CHECK(unit_type IN ('kg', 'piece', 'meter')),
                base_price INTEGER NOT NULL,
                min_order REAL DEFAULT 1.0,
                est_hours INTEGER DEFAULT 24,
                icon TEXT,
                description TEXT,
                is_active INTEGER DEFAULT 1,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL
            );
            CREATE INDEX IF NOT EXISTS idx_services_category ON services(category_id);

            CREATE TABLE IF NOT EXISTS branch_service_prices (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                branch_id INTEGER NOT NULL REFERENCES branches(id) ON DELETE CASCADE,
                service_id INTEGER NOT NULL REFERENCES services(id) ON DELETE CASCADE,
                price INTEGER NOT NULL,
                min_order REAL DEFAULT 1.0,
                est_hours INTEGER DEFAULT 24,
                is_active INTEGER DEFAULT 1,
                updated_at TEXT NOT NULL,
                UNIQUE(branch_id, service_id)
            );

            CREATE TABLE IF NOT EXISTS service_price_history (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                service_id INTEGER NOT NULL REFERENCES services(id) ON DELETE CASCADE,
                branch_id INTEGER REFERENCES branches(id) ON DELETE SET NULL,
                old_price INTEGER NOT NULL,
                new_price INTEGER NOT NULL,
                changed_by INTEGER REFERENCES users(id) ON DELETE SET NULL,
                created_at TEXT NOT NULL
            );

            CREATE TABLE IF NOT EXISTS service_addons (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                company_id INTEGER NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
                name TEXT NOT NULL,
                price INTEGER NOT NULL,
                unit_type TEXT DEFAULT 'item',
                description TEXT,
                is_active INTEGER DEFAULT 1,
                created_at TEXT NOT NULL
            );

            CREATE TABLE IF NOT EXISTS promotions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                company_id INTEGER NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
                name TEXT NOT NULL,
                banner TEXT,
                description TEXT,
                discount_type TEXT CHECK(discount_type IN ('percent', 'fixed')),
                discount_value INTEGER NOT NULL,
                start_date TEXT NOT NULL,
                end_date TEXT NOT NULL,
                is_active INTEGER DEFAULT 1,
                created_at TEXT NOT NULL
            );
        ");

        // D. Pesanan dan transaksi
        $db->exec("
            CREATE TABLE IF NOT EXISTS orders (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                company_id INTEGER NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
                branch_id INTEGER NOT NULL REFERENCES branches(id) ON DELETE CASCADE,
                customer_id INTEGER NOT NULL REFERENCES customers(id) ON DELETE RESTRICT,
                user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE RESTRICT,
                order_no TEXT NOT NULL UNIQUE,
                tracking_token TEXT NOT NULL UNIQUE,
                order_type TEXT DEFAULT 'regular' CHECK(order_type IN ('regular', 'express', 'priority')),
                est_completion_at TEXT,
                actual_completion_at TEXT,
                total_weight REAL DEFAULT 0,
                total_pieces INTEGER DEFAULT 0,
                subtotal INTEGER NOT NULL DEFAULT 0,
                addon_total INTEGER NOT NULL DEFAULT 0,
                discount_total INTEGER NOT NULL DEFAULT 0,
                voucher_code TEXT,
                voucher_discount INTEGER NOT NULL DEFAULT 0,
                tax_amount INTEGER NOT NULL DEFAULT 0,
                delivery_fee INTEGER NOT NULL DEFAULT 0,
                grand_total INTEGER NOT NULL DEFAULT 0,
                paid_amount INTEGER NOT NULL DEFAULT 0,
                balance_due INTEGER NOT NULL DEFAULT 0,
                payment_status TEXT DEFAULT 'unpaid' CHECK(payment_status IN ('unpaid', 'partial', 'paid', 'refunded')),
                production_status TEXT DEFAULT 'received' CHECK(production_status IN (
                    'received', 'queued', 'in_process', 'qc', 'packaging', 'ready', 'delivering', 'completed', 'cancelled'
                )),
                notes TEXT,
                customer_notes TEXT,
                cancelled_at TEXT,
                cancellation_reason TEXT,
                cancelled_by INTEGER REFERENCES users(id) ON DELETE SET NULL,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL
            );
            CREATE INDEX IF NOT EXISTS idx_orders_branch_created ON orders(branch_id, created_at);
            CREATE INDEX IF NOT EXISTS idx_orders_customer ON orders(customer_id);
            CREATE INDEX IF NOT EXISTS idx_orders_status ON orders(production_status, payment_status);

            CREATE TABLE IF NOT EXISTS order_items (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                order_id INTEGER NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
                service_id INTEGER NOT NULL REFERENCES services(id) ON DELETE RESTRICT,
                service_name TEXT NOT NULL,
                unit_type TEXT NOT NULL,
                unit_price INTEGER NOT NULL,
                quantity INTEGER DEFAULT 1,
                weight REAL DEFAULT 0,
                subtotal INTEGER NOT NULL,
                notes TEXT,
                created_at TEXT NOT NULL
            );
            CREATE INDEX IF NOT EXISTS idx_order_items_order ON order_items(order_id);

            CREATE TABLE IF NOT EXISTS order_item_details (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                order_item_id INTEGER NOT NULL REFERENCES order_items(id) ON DELETE CASCADE,
                item_name TEXT NOT NULL,
                color TEXT,
                brand TEXT,
                defect_notes TEXT,
                quantity INTEGER DEFAULT 1,
                created_at TEXT NOT NULL
            );

            CREATE TABLE IF NOT EXISTS order_status_history (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                order_id INTEGER NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
                from_status TEXT,
                to_status TEXT NOT NULL,
                notes TEXT,
                changed_by_user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
                created_at TEXT NOT NULL
            );
            CREATE INDEX IF NOT EXISTS idx_order_history ON order_status_history(order_id);

            CREATE TABLE IF NOT EXISTS order_assignments (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                order_id INTEGER NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
                user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
                role_type TEXT NOT NULL,
                assigned_at TEXT NOT NULL,
                notes TEXT
            );

            CREATE TABLE IF NOT EXISTS order_qr_codes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                order_id INTEGER NOT NULL UNIQUE REFERENCES orders(id) ON DELETE CASCADE,
                code TEXT NOT NULL,
                token TEXT NOT NULL UNIQUE,
                qr_image_path TEXT,
                created_at TEXT NOT NULL
            );

            CREATE TABLE IF NOT EXISTS order_notes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                order_id INTEGER NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
                user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
                note TEXT NOT NULL,
                is_customer_visible INTEGER DEFAULT 0,
                created_at TEXT NOT NULL
            );

            CREATE TABLE IF NOT EXISTS order_attachments (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                order_id INTEGER NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
                file_path TEXT NOT NULL,
                file_name TEXT NOT NULL,
                file_type TEXT,
                file_size INTEGER,
                caption TEXT,
                created_at TEXT NOT NULL
            );

            CREATE TABLE IF NOT EXISTS order_cancellations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                order_id INTEGER NOT NULL UNIQUE REFERENCES orders(id) ON DELETE CASCADE,
                user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE RESTRICT,
                reason TEXT NOT NULL,
                refund_amount INTEGER DEFAULT 0,
                approved_by INTEGER REFERENCES users(id) ON DELETE SET NULL,
                created_at TEXT NOT NULL
            );
        ");

        // E. Produksi dan pemeriksaan
        $db->exec("
            CREATE TABLE IF NOT EXISTS production_stages (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                company_id INTEGER NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
                name TEXT NOT NULL,
                slug TEXT NOT NULL,
                sort_order INTEGER DEFAULT 0,
                est_minutes INTEGER DEFAULT 60,
                is_required INTEGER DEFAULT 1,
                created_at TEXT NOT NULL
            );

            CREATE TABLE IF NOT EXISTS production_jobs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                order_id INTEGER NOT NULL UNIQUE REFERENCES orders(id) ON DELETE CASCADE,
                current_stage_id INTEGER REFERENCES production_stages(id) ON DELETE SET NULL,
                status TEXT DEFAULT 'waiting' CHECK(status IN ('waiting', 'in_progress', 'completed', 'hold')),
                assigned_user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
                machine_id INTEGER,
                started_at TEXT,
                completed_at TEXT,
                notes TEXT,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL
            );
            CREATE INDEX IF NOT EXISTS idx_prod_jobs_order ON production_jobs(order_id);

            CREATE TABLE IF NOT EXISTS production_job_items (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                job_id INTEGER NOT NULL REFERENCES production_jobs(id) ON DELETE CASCADE,
                order_item_id INTEGER NOT NULL REFERENCES order_items(id) ON DELETE CASCADE,
                quantity_checked INTEGER DEFAULT 0,
                notes TEXT,
                created_at TEXT NOT NULL
            );

            CREATE TABLE IF NOT EXISTS production_stage_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                job_id INTEGER NOT NULL REFERENCES production_jobs(id) ON DELETE CASCADE,
                stage_id INTEGER NOT NULL REFERENCES production_stages(id) ON DELETE CASCADE,
                user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
                machine_id INTEGER,
                status TEXT DEFAULT 'completed',
                started_at TEXT,
                completed_at TEXT,
                notes TEXT,
                created_at TEXT NOT NULL
            );
            CREATE INDEX IF NOT EXISTS idx_prod_stage_logs ON production_stage_logs(job_id);

            CREATE TABLE IF NOT EXISTS quality_checks (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                order_id INTEGER NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
                job_id INTEGER REFERENCES production_jobs(id) ON DELETE CASCADE,
                checked_by_user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
                cleanliness_score INTEGER DEFAULT 5,
                fragrance_score INTEGER DEFAULT 5,
                neatness_score INTEGER DEFAULT 5,
                completeness_score INTEGER DEFAULT 5,
                is_passed INTEGER DEFAULT 1,
                defect_notes TEXT,
                checked_at TEXT NOT NULL
            );

            CREATE TABLE IF NOT EXISTS quality_check_items (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                quality_check_id INTEGER NOT NULL REFERENCES quality_checks(id) ON DELETE CASCADE,
                item_name TEXT NOT NULL,
                passed INTEGER DEFAULT 1,
                defect_description TEXT,
                created_at TEXT NOT NULL
            );

            CREATE TABLE IF NOT EXISTS production_machine_usage (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                job_id INTEGER REFERENCES production_jobs(id) ON DELETE CASCADE,
                machine_id INTEGER NOT NULL,
                user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
                started_at TEXT NOT NULL,
                finished_at TEXT,
                duration_minutes INTEGER DEFAULT 0,
                load_kg REAL DEFAULT 0,
                created_at TEXT NOT NULL
            );
        ");

        // F. Pembayaran
        $db->exec("
            CREATE TABLE IF NOT EXISTS payment_methods (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                company_id INTEGER NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
                code TEXT NOT NULL UNIQUE,
                name TEXT NOT NULL,
                category TEXT NOT NULL CHECK(category IN ('cash', 'transfer', 'qris', 'deposit', 'card')),
                is_active INTEGER DEFAULT 1,
                account_number TEXT,
                account_holder TEXT,
                config TEXT,
                created_at TEXT NOT NULL
            );

            CREATE TABLE IF NOT EXISTS payments (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                company_id INTEGER NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
                branch_id INTEGER NOT NULL REFERENCES branches(id) ON DELETE CASCADE,
                order_id INTEGER NOT NULL REFERENCES orders(id) ON DELETE RESTRICT,
                customer_id INTEGER NOT NULL REFERENCES customers(id) ON DELETE RESTRICT,
                payment_no TEXT NOT NULL UNIQUE,
                amount INTEGER NOT NULL,
                payment_method_id INTEGER NOT NULL REFERENCES payment_methods(id),
                payment_type TEXT DEFAULT 'full' CHECK(payment_type IN ('full', 'dp', 'settlement', 'refund')),
                status TEXT DEFAULT 'success' CHECK(status IN ('pending', 'success', 'failed', 'expired')),
                reference_no TEXT,
                proof_file TEXT,
                notes TEXT,
                received_by_user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
                paid_at TEXT NOT NULL,
                created_at TEXT NOT NULL
            );
            CREATE INDEX IF NOT EXISTS idx_payments_order ON payments(order_id);
            CREATE INDEX IF NOT EXISTS idx_payments_branch ON payments(branch_id);

            CREATE TABLE IF NOT EXISTS payment_gateway_transactions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                payment_id INTEGER REFERENCES payments(id) ON DELETE SET NULL,
                gateway_name TEXT NOT NULL,
                transaction_id TEXT NOT NULL UNIQUE,
                order_id INTEGER NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
                amount INTEGER NOT NULL,
                qr_string TEXT,
                qr_url TEXT,
                status TEXT DEFAULT 'pending' CHECK(status IN ('pending', 'paid', 'expired', 'failed')),
                raw_response TEXT,
                idempotency_key TEXT UNIQUE,
                expires_at TEXT,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL
            );

            CREATE TABLE IF NOT EXISTS payment_webhook_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                gateway_name TEXT NOT NULL,
                event_type TEXT,
                payload TEXT NOT NULL,
                signature TEXT,
                is_verified INTEGER DEFAULT 0,
                processed_at TEXT,
                created_at TEXT NOT NULL
            );

            CREATE TABLE IF NOT EXISTS customer_deposits (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                company_id INTEGER NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
                customer_id INTEGER NOT NULL UNIQUE REFERENCES customers(id) ON DELETE CASCADE,
                balance INTEGER NOT NULL DEFAULT 0,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL
            );

            CREATE TABLE IF NOT EXISTS deposit_transactions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                customer_id INTEGER NOT NULL REFERENCES customers(id) ON DELETE CASCADE,
                branch_id INTEGER NOT NULL REFERENCES branches(id) ON DELETE CASCADE,
                order_id INTEGER REFERENCES orders(id) ON DELETE SET NULL,
                type TEXT NOT NULL CHECK(type IN ('credit', 'debit')),
                amount INTEGER NOT NULL,
                balance_after INTEGER NOT NULL,
                description TEXT,
                reference_no TEXT,
                created_at TEXT NOT NULL
            );

            CREATE TABLE IF NOT EXISTS refunds (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                payment_id INTEGER REFERENCES payments(id) ON DELETE RESTRICT,
                order_id INTEGER NOT NULL REFERENCES orders(id) ON DELETE RESTRICT,
                amount INTEGER NOT NULL,
                reason TEXT NOT NULL,
                approved_by_user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
                status TEXT DEFAULT 'completed' CHECK(status IN ('pending', 'completed', 'rejected')),
                refunded_at TEXT NOT NULL,
                created_at TEXT NOT NULL
            );
        ");

        // G. Pickup dan delivery
        $db->exec("
            CREATE TABLE IF NOT EXISTS delivery_requests (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                company_id INTEGER NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
                branch_id INTEGER NOT NULL REFERENCES branches(id) ON DELETE CASCADE,
                order_id INTEGER REFERENCES orders(id) ON DELETE SET NULL,
                customer_id INTEGER NOT NULL REFERENCES customers(id) ON DELETE CASCADE,
                type TEXT NOT NULL CHECK(type IN ('pickup', 'delivery')),
                customer_address_id INTEGER REFERENCES customer_addresses(id) ON DELETE SET NULL,
                address_text TEXT NOT NULL,
                schedule_date TEXT NOT NULL,
                schedule_time TEXT,
                status TEXT DEFAULT 'pending' CHECK(status IN (
                    'pending', 'assigned', 'on_the_way', 'picked_up', 'delivered', 'failed', 'cancelled'
                )),
                courier_user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
                fee INTEGER DEFAULT 0,
                notes TEXT,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL
            );
            CREATE INDEX IF NOT EXISTS idx_deliv_branch ON delivery_requests(branch_id);

            CREATE TABLE IF NOT EXISTS delivery_assignments (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                delivery_request_id INTEGER NOT NULL REFERENCES delivery_requests(id) ON DELETE CASCADE,
                courier_user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
                assigned_at TEXT NOT NULL,
                status TEXT DEFAULT 'assigned',
                notes TEXT
            );

            CREATE TABLE IF NOT EXISTS delivery_status_history (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                delivery_request_id INTEGER NOT NULL REFERENCES delivery_requests(id) ON DELETE CASCADE,
                from_status TEXT,
                to_status TEXT NOT NULL,
                user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
                notes TEXT,
                created_at TEXT NOT NULL
            );

            CREATE TABLE IF NOT EXISTS delivery_proofs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                delivery_request_id INTEGER NOT NULL REFERENCES delivery_requests(id) ON DELETE CASCADE,
                photo_path TEXT NOT NULL,
                recipient_name TEXT NOT NULL,
                signed_at TEXT NOT NULL,
                notes TEXT,
                created_at TEXT NOT NULL
            );

            CREATE TABLE IF NOT EXISTS delivery_fees (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                branch_id INTEGER NOT NULL REFERENCES branches(id) ON DELETE CASCADE,
                min_distance_km REAL NOT NULL,
                max_distance_km REAL NOT NULL,
                fee INTEGER NOT NULL,
                created_at TEXT NOT NULL
            );
        ");

        // H. Inventaris
        $db->exec("
            CREATE TABLE IF NOT EXISTS inventory_categories (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                company_id INTEGER NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
                name TEXT NOT NULL,
                slug TEXT NOT NULL,
                description TEXT,
                created_at TEXT NOT NULL
            );

            CREATE TABLE IF NOT EXISTS inventory_items (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                company_id INTEGER NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
                category_id INTEGER NOT NULL REFERENCES inventory_categories(id) ON DELETE CASCADE,
                code TEXT NOT NULL UNIQUE,
                name TEXT NOT NULL,
                unit TEXT NOT NULL,
                min_stock REAL DEFAULT 5,
                cost_price INTEGER DEFAULT 0,
                description TEXT,
                is_active INTEGER DEFAULT 1,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL
            );

            CREATE TABLE IF NOT EXISTS branch_inventory (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                branch_id INTEGER NOT NULL REFERENCES branches(id) ON DELETE CASCADE,
                item_id INTEGER NOT NULL REFERENCES inventory_items(id) ON DELETE CASCADE,
                current_stock REAL DEFAULT 0,
                min_stock REAL DEFAULT 5,
                updated_at TEXT NOT NULL,
                UNIQUE(branch_id, item_id)
            );
            CREATE INDEX IF NOT EXISTS idx_branch_inventory ON branch_inventory(branch_id, item_id);

            CREATE TABLE IF NOT EXISTS stock_movements (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                company_id INTEGER NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
                branch_id INTEGER NOT NULL REFERENCES branches(id) ON DELETE CASCADE,
                item_id INTEGER NOT NULL REFERENCES inventory_items(id) ON DELETE RESTRICT,
                type TEXT NOT NULL CHECK(type IN ('in', 'out', 'transfer_in', 'transfer_out', 'adjustment', 'opname')),
                quantity REAL NOT NULL,
                stock_before REAL NOT NULL,
                stock_after REAL NOT NULL,
                reference_type TEXT,
                reference_id INTEGER,
                notes TEXT,
                user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
                created_at TEXT NOT NULL
            );
            CREATE INDEX IF NOT EXISTS idx_stock_movements ON stock_movements(branch_id, item_id);

            CREATE TABLE IF NOT EXISTS stock_movement_items (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                movement_id INTEGER NOT NULL REFERENCES stock_movements(id) ON DELETE CASCADE,
                item_id INTEGER NOT NULL REFERENCES inventory_items(id) ON DELETE RESTRICT,
                quantity REAL NOT NULL,
                notes TEXT,
                created_at TEXT NOT NULL
            );

            CREATE TABLE IF NOT EXISTS suppliers (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                company_id INTEGER NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
                code TEXT NOT NULL UNIQUE,
                name TEXT NOT NULL,
                phone TEXT,
                email TEXT,
                address TEXT,
                contact_person TEXT,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL
            );

            CREATE TABLE IF NOT EXISTS purchase_orders (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                company_id INTEGER NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
                branch_id INTEGER NOT NULL REFERENCES branches(id) ON DELETE CASCADE,
                supplier_id INTEGER NOT NULL REFERENCES suppliers(id) ON DELETE RESTRICT,
                po_no TEXT NOT NULL UNIQUE,
                total_amount INTEGER DEFAULT 0,
                status TEXT DEFAULT 'draft' CHECK(status IN ('draft', 'ordered', 'received', 'cancelled')),
                notes TEXT,
                created_by INTEGER REFERENCES users(id) ON DELETE SET NULL,
                order_date TEXT NOT NULL,
                received_date TEXT,
                created_at TEXT NOT NULL
            );

            CREATE TABLE IF NOT EXISTS purchase_order_items (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                po_id INTEGER NOT NULL REFERENCES purchase_orders(id) ON DELETE CASCADE,
                item_id INTEGER NOT NULL REFERENCES inventory_items(id) ON DELETE RESTRICT,
                quantity REAL NOT NULL,
                unit_price INTEGER NOT NULL,
                subtotal INTEGER NOT NULL,
                received_qty REAL DEFAULT 0,
                created_at TEXT NOT NULL
            );

            CREATE TABLE IF NOT EXISTS stock_opnames (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                company_id INTEGER NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
                branch_id INTEGER NOT NULL REFERENCES branches(id) ON DELETE CASCADE,
                opname_no TEXT NOT NULL UNIQUE,
                opname_date TEXT NOT NULL,
                status TEXT DEFAULT 'draft' CHECK(status IN ('draft', 'completed')),
                conducted_by INTEGER REFERENCES users(id) ON DELETE SET NULL,
                notes TEXT,
                created_at TEXT NOT NULL
            );

            CREATE TABLE IF NOT EXISTS stock_opname_items (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                opname_id INTEGER NOT NULL REFERENCES stock_opnames(id) ON DELETE CASCADE,
                item_id INTEGER NOT NULL REFERENCES inventory_items(id) ON DELETE RESTRICT,
                system_stock REAL NOT NULL,
                physical_stock REAL NOT NULL,
                difference REAL NOT NULL,
                reason TEXT,
                created_at TEXT NOT NULL
            );

            CREATE TABLE IF NOT EXISTS stock_transfers (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                company_id INTEGER NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
                from_branch_id INTEGER NOT NULL REFERENCES branches(id) ON DELETE RESTRICT,
                to_branch_id INTEGER NOT NULL REFERENCES branches(id) ON DELETE RESTRICT,
                transfer_no TEXT NOT NULL UNIQUE,
                status TEXT DEFAULT 'pending' CHECK(status IN ('pending', 'in_transit', 'received', 'rejected')),
                requested_by INTEGER REFERENCES users(id) ON DELETE SET NULL,
                sent_date TEXT,
                received_date TEXT,
                notes TEXT,
                created_at TEXT NOT NULL
            );

            CREATE TABLE IF NOT EXISTS stock_transfer_items (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                transfer_id INTEGER NOT NULL REFERENCES stock_transfers(id) ON DELETE CASCADE,
                item_id INTEGER NOT NULL REFERENCES inventory_items(id) ON DELETE RESTRICT,
                quantity REAL NOT NULL,
                received_qty REAL DEFAULT 0,
                notes TEXT,
                created_at TEXT NOT NULL
            );
        ");

        // I. Mesin
        $db->exec("
            CREATE TABLE IF NOT EXISTS machine_types (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                company_id INTEGER NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
                name TEXT NOT NULL,
                capacity_kg REAL DEFAULT 0,
                description TEXT,
                created_at TEXT NOT NULL
            );

            CREATE TABLE IF NOT EXISTS machines (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                company_id INTEGER NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
                branch_id INTEGER NOT NULL REFERENCES branches(id) ON DELETE CASCADE,
                type_id INTEGER NOT NULL REFERENCES machine_types(id) ON DELETE RESTRICT,
                code TEXT NOT NULL UNIQUE,
                name TEXT NOT NULL,
                brand TEXT,
                model TEXT,
                serial_number TEXT,
                capacity_kg REAL DEFAULT 0,
                purchase_date TEXT,
                condition TEXT DEFAULT 'good' CHECK(condition IN ('good', 'needs_repair', 'damaged')),
                operational_status TEXT DEFAULT 'idle' CHECK(operational_status IN ('idle', 'in_use', 'maintenance', 'offline')),
                total_cycles INTEGER DEFAULT 0,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL
            );
            CREATE INDEX IF NOT EXISTS idx_machines_branch ON machines(branch_id);

            CREATE TABLE IF NOT EXISTS machine_maintenance (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                machine_id INTEGER NOT NULL REFERENCES machines(id) ON DELETE CASCADE,
                branch_id INTEGER NOT NULL REFERENCES branches(id) ON DELETE CASCADE,
                title TEXT NOT NULL,
                maintenance_type TEXT DEFAULT 'routine' CHECK(maintenance_type IN ('routine', 'repair')),
                scheduled_date TEXT NOT NULL,
                completed_date TEXT,
                cost INTEGER DEFAULT 0,
                technician_name TEXT,
                status TEXT DEFAULT 'scheduled' CHECK(status IN ('scheduled', 'in_progress', 'completed', 'cancelled')),
                notes TEXT,
                created_at TEXT NOT NULL
            );

            CREATE TABLE IF NOT EXISTS machine_maintenance_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                maintenance_id INTEGER NOT NULL REFERENCES machine_maintenance(id) ON DELETE CASCADE,
                machine_id INTEGER NOT NULL REFERENCES machines(id) ON DELETE CASCADE,
                action_taken TEXT NOT NULL,
                parts_replaced TEXT,
                notes TEXT,
                created_at TEXT NOT NULL
            );

            CREATE TABLE IF NOT EXISTS machine_faults (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                machine_id INTEGER NOT NULL REFERENCES machines(id) ON DELETE CASCADE,
                reported_by INTEGER REFERENCES users(id) ON DELETE SET NULL,
                fault_date TEXT NOT NULL,
                description TEXT NOT NULL,
                severity TEXT DEFAULT 'medium' CHECK(severity IN ('low', 'medium', 'high', 'critical')),
                resolved_at TEXT,
                resolved_by INTEGER REFERENCES users(id) ON DELETE SET NULL,
                status TEXT DEFAULT 'open' CHECK(status IN ('open', 'investigating', 'resolved')),
                created_at TEXT NOT NULL
            );
        ");

        // J. Keuangan dan akuntansi
        $db->exec("
            CREATE TABLE IF NOT EXISTS accounts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                company_id INTEGER NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
                code TEXT NOT NULL UNIQUE,
                name TEXT NOT NULL,
                type TEXT NOT NULL CHECK(type IN ('asset', 'liability', 'equity', 'revenue', 'expense')),
                sub_type TEXT,
                normal_balance TEXT NOT NULL CHECK(normal_balance IN ('debit', 'credit')),
                is_active INTEGER DEFAULT 1,
                created_at TEXT NOT NULL
            );

            CREATE TABLE IF NOT EXISTS journal_entries (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                company_id INTEGER NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
                branch_id INTEGER REFERENCES branches(id) ON DELETE SET NULL,
                entry_no TEXT NOT NULL UNIQUE,
                entry_date TEXT NOT NULL,
                reference_type TEXT,
                reference_id INTEGER,
                reference_no TEXT,
                description TEXT NOT NULL,
                total_debit INTEGER NOT NULL DEFAULT 0,
                total_credit INTEGER NOT NULL DEFAULT 0,
                is_balanced INTEGER DEFAULT 1,
                status TEXT DEFAULT 'posted' CHECK(status IN ('posted', 'reversed')),
                created_by INTEGER REFERENCES users(id) ON DELETE SET NULL,
                created_at TEXT NOT NULL
            );
            CREATE INDEX IF NOT EXISTS idx_journal_branch_date ON journal_entries(branch_id, entry_date);

            CREATE TABLE IF NOT EXISTS journal_entry_lines (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                journal_entry_id INTEGER NOT NULL REFERENCES journal_entries(id) ON DELETE CASCADE,
                account_id INTEGER NOT NULL REFERENCES accounts(id) ON DELETE RESTRICT,
                debit INTEGER NOT NULL DEFAULT 0,
                credit INTEGER NOT NULL DEFAULT 0,
                memo TEXT,
                created_at TEXT NOT NULL
            );
            CREATE INDEX IF NOT EXISTS idx_journal_lines_acc ON journal_entry_lines(account_id);

            CREATE TABLE IF NOT EXISTS cash_accounts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                company_id INTEGER NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
                branch_id INTEGER NOT NULL REFERENCES branches(id) ON DELETE CASCADE,
                name TEXT NOT NULL,
                account_id INTEGER REFERENCES accounts(id) ON DELETE RESTRICT,
                account_type TEXT DEFAULT 'cash' CHECK(account_type IN ('cash', 'bank')),
                balance INTEGER DEFAULT 0,
                is_active INTEGER DEFAULT 1,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL
            );

            CREATE TABLE IF NOT EXISTS cash_transactions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                company_id INTEGER NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
                branch_id INTEGER NOT NULL REFERENCES branches(id) ON DELETE CASCADE,
                cash_account_id INTEGER NOT NULL REFERENCES cash_accounts(id) ON DELETE CASCADE,
                type TEXT NOT NULL CHECK(type IN ('in', 'out')),
                amount INTEGER NOT NULL,
                balance_after INTEGER NOT NULL,
                category TEXT,
                reference_no TEXT,
                description TEXT,
                user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
                transaction_date TEXT NOT NULL,
                created_at TEXT NOT NULL
            );

            CREATE TABLE IF NOT EXISTS expense_categories (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                company_id INTEGER NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
                name TEXT NOT NULL,
                account_id INTEGER REFERENCES accounts(id) ON DELETE SET NULL,
                description TEXT,
                is_active INTEGER DEFAULT 1,
                created_at TEXT NOT NULL
            );

            CREATE TABLE IF NOT EXISTS expenses (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                company_id INTEGER NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
                branch_id INTEGER NOT NULL REFERENCES branches(id) ON DELETE CASCADE,
                category_id INTEGER NOT NULL REFERENCES expense_categories(id) ON DELETE RESTRICT,
                cash_account_id INTEGER REFERENCES cash_accounts(id) ON DELETE RESTRICT,
                expense_no TEXT NOT NULL UNIQUE,
                amount INTEGER NOT NULL,
                expense_date TEXT NOT NULL,
                paid_to TEXT,
                description TEXT,
                receipt_file TEXT,
                approved_by INTEGER REFERENCES users(id) ON DELETE SET NULL,
                status TEXT DEFAULT 'approved' CHECK(status IN ('approved', 'pending', 'rejected')),
                created_by INTEGER REFERENCES users(id) ON DELETE SET NULL,
                created_at TEXT NOT NULL
            );
            CREATE INDEX IF NOT EXISTS idx_expenses_branch ON expenses(branch_id, expense_date);

            CREATE TABLE IF NOT EXISTS receivables (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                company_id INTEGER NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
                branch_id INTEGER NOT NULL REFERENCES branches(id) ON DELETE CASCADE,
                customer_id INTEGER NOT NULL REFERENCES customers(id) ON DELETE RESTRICT,
                order_id INTEGER NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
                amount INTEGER NOT NULL,
                paid_amount INTEGER DEFAULT 0,
                balance INTEGER NOT NULL,
                due_date TEXT,
                status TEXT DEFAULT 'unpaid' CHECK(status IN ('unpaid', 'partial', 'paid')),
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL
            );

            CREATE TABLE IF NOT EXISTS payables (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                company_id INTEGER NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
                branch_id INTEGER NOT NULL REFERENCES branches(id) ON DELETE CASCADE,
                supplier_id INTEGER NOT NULL REFERENCES suppliers(id) ON DELETE RESTRICT,
                po_id INTEGER REFERENCES purchase_orders(id) ON DELETE SET NULL,
                amount INTEGER NOT NULL,
                paid_amount INTEGER DEFAULT 0,
                balance INTEGER NOT NULL,
                due_date TEXT,
                status TEXT DEFAULT 'unpaid' CHECK(status IN ('unpaid', 'partial', 'paid')),
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL
            );

            CREATE TABLE IF NOT EXISTS fiscal_periods (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                company_id INTEGER NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
                name TEXT NOT NULL,
                start_date TEXT NOT NULL,
                end_date TEXT NOT NULL,
                is_closed INTEGER DEFAULT 0,
                closed_at TEXT,
                closed_by INTEGER REFERENCES users(id) ON DELETE SET NULL,
                created_at TEXT NOT NULL
            );
        ");

        // K. WhatsApp dan notifikasi
        $db->exec("
            CREATE TABLE IF NOT EXISTS whatsapp_settings (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                company_id INTEGER NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
                provider TEXT DEFAULT 'official' CHECK(provider IN ('official', 'wablas', 'fonnte', 'mock')),
                api_url TEXT,
                api_key TEXT,
                sender_number TEXT,
                is_active INTEGER DEFAULT 1,
                updated_at TEXT NOT NULL
            );

            CREATE TABLE IF NOT EXISTS whatsapp_templates (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                company_id INTEGER NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
                trigger_event TEXT NOT NULL,
                title TEXT NOT NULL,
                message_template TEXT NOT NULL,
                variables TEXT,
                is_active INTEGER DEFAULT 1,
                updated_at TEXT NOT NULL,
                UNIQUE(company_id, trigger_event)
            );

            CREATE TABLE IF NOT EXISTS whatsapp_queue (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                company_id INTEGER NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
                branch_id INTEGER REFERENCES branches(id) ON DELETE SET NULL,
                phone_number TEXT NOT NULL,
                recipient_name TEXT,
                message TEXT NOT NULL,
                trigger_event TEXT NOT NULL,
                reference_type TEXT,
                reference_id INTEGER,
                status TEXT DEFAULT 'pending' CHECK(status IN ('pending', 'processing', 'sent', 'failed')),
                attempts INTEGER DEFAULT 0,
                max_attempts INTEGER DEFAULT 3,
                last_error TEXT,
                scheduled_at TEXT NOT NULL,
                sent_at TEXT,
                created_at TEXT NOT NULL
            );
            CREATE INDEX IF NOT EXISTS idx_wa_queue_status ON whatsapp_queue(status, scheduled_at);

            CREATE TABLE IF NOT EXISTS whatsapp_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                company_id INTEGER NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
                queue_id INTEGER REFERENCES whatsapp_queue(id) ON DELETE SET NULL,
                phone_number TEXT NOT NULL,
                message TEXT NOT NULL,
                response_payload TEXT,
                status TEXT NOT NULL,
                created_at TEXT NOT NULL
            );

            CREATE TABLE IF NOT EXISTS notification_settings (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                company_id INTEGER NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
                event_name TEXT NOT NULL,
                is_whatsapp_enabled INTEGER DEFAULT 1,
                is_inapp_enabled INTEGER DEFAULT 1,
                created_at TEXT NOT NULL
            );

            CREATE TABLE IF NOT EXISTS notifications (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
                title TEXT NOT NULL,
                message TEXT NOT NULL,
                url TEXT,
                is_read INTEGER DEFAULT 0,
                read_at TEXT,
                created_at TEXT NOT NULL
            );
            CREATE INDEX IF NOT EXISTS idx_notifications_user ON notifications(user_id, is_read);
        ");

        // L. Sistem
        $db->exec("
            CREATE TABLE IF NOT EXISTS app_settings (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                `key` TEXT NOT NULL UNIQUE,
                `value` TEXT,
                group_name TEXT DEFAULT 'general',
                description TEXT,
                updated_at TEXT NOT NULL
            );

            CREATE TABLE IF NOT EXISTS branch_settings (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                branch_id INTEGER NOT NULL REFERENCES branches(id) ON DELETE CASCADE,
                `key` TEXT NOT NULL,
                `value` TEXT,
                updated_at TEXT NOT NULL,
                UNIQUE(branch_id, `key`)
            );

            CREATE TABLE IF NOT EXISTS receipt_settings (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                company_id INTEGER NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
                branch_id INTEGER REFERENCES branches(id) ON DELETE CASCADE,
                paper_size TEXT DEFAULT '58mm' CHECK(paper_size IN ('58mm', '80mm')),
                header_text TEXT,
                footer_text TEXT,
                show_logo INTEGER DEFAULT 1,
                show_qr INTEGER DEFAULT 1,
                show_barcode INTEGER DEFAULT 0,
                show_notes INTEGER DEFAULT 1,
                updated_at TEXT NOT NULL
            );

            CREATE TABLE IF NOT EXISTS api_integrations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                company_id INTEGER NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
                provider_type TEXT NOT NULL,
                config_json TEXT,
                is_active INTEGER DEFAULT 1,
                updated_at TEXT NOT NULL
            );

            CREATE TABLE IF NOT EXISTS backup_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                company_id INTEGER NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
                file_name TEXT NOT NULL,
                file_size INTEGER NOT NULL,
                backup_type TEXT DEFAULT 'manual' CHECK(backup_type IN ('manual', 'auto')),
                file_path TEXT NOT NULL,
                created_by INTEGER REFERENCES users(id) ON DELETE SET NULL,
                status TEXT DEFAULT 'success',
                created_at TEXT NOT NULL
            );

            CREATE TABLE IF NOT EXISTS system_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                level TEXT NOT NULL,
                message TEXT NOT NULL,
                context_json TEXT,
                ip TEXT,
                created_at TEXT NOT NULL
            );

            CREATE TABLE IF NOT EXISTS sequences (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                branch_id INTEGER NOT NULL REFERENCES branches(id) ON DELETE CASCADE,
                type TEXT NOT NULL,
                prefix TEXT NOT NULL,
                current_val INTEGER DEFAULT 0,
                date_code TEXT NOT NULL,
                updated_at TEXT NOT NULL,
                UNIQUE(branch_id, type, date_code)
            );
        ");
    }

    public function down(): void
    {
        // Tables can be dropped if migration rolled back
    }
};
