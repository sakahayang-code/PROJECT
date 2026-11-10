<?php

use App\Core\Router;
use App\Middleware\AuthMiddleware;
use App\Middleware\GuestMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Middleware\CustomerAuthMiddleware;
use App\Modules\Users\AuthController;
use App\Modules\Dashboard\DashboardController;
use App\Modules\Branches\BranchController;

// Public & Guest Routes
Router::get('/', function() {
    if (\App\Core\Auth::check()) {
        \App\Core\Response::redirect(url('/dashboard'));
    } else {
        \App\Core\Response::redirect(url('/login'));
    }
});

Router::group(['middleware' => [GuestMiddleware::class]], function() {
    Router::get('/login', [AuthController::class, 'showLogin']);
    Router::post('/login', [AuthController::class, 'login'], [CsrfMiddleware::class]);
});

Router::post('/logout', [AuthController::class, 'logout'], [AuthMiddleware::class, CsrfMiddleware::class]);

// Authenticated Staff Routes
Router::group(['middleware' => [AuthMiddleware::class]], function() {
    // Dashboard
    Router::get('/dashboard', [DashboardController::class, 'index']);

    // Profile
    Router::get('/profile', [AuthController::class, 'showProfile']);
    Router::post('/profile/update', [AuthController::class, 'updateProfile'], [CsrfMiddleware::class]);
    Router::post('/profile/password', [AuthController::class, 'updatePassword'], [CsrfMiddleware::class]);

    // Branch Switcher & Management
    Router::get('/branches/switch', [BranchController::class, 'switchBranch']);
    Router::get('/branches', [BranchController::class, 'index']);
    Router::post('/branches', [BranchController::class, 'store'], [CsrfMiddleware::class]);
    Router::post('/branches/{id}', [BranchController::class, 'update'], [CsrfMiddleware::class]);

    // Users Management
    Router::get('/users', [\App\Modules\Users\UserController::class, 'index']);
    Router::post('/users', [\App\Modules\Users\UserController::class, 'store'], [CsrfMiddleware::class]);
    Router::post('/users/{id}', [\App\Modules\Users\UserController::class, 'update'], [CsrfMiddleware::class]);

    // Roles & RBAC Matrix
    Router::get('/roles', [\App\Modules\Users\RoleController::class, 'index']);
    Router::get('/roles/{id}/permissions', [\App\Modules\Users\RoleController::class, 'permissions']);
    Router::post('/roles/{id}/permissions', [\App\Modules\Users\RoleController::class, 'updatePermissions'], [CsrfMiddleware::class]);

    // Employees, Shifts & Attendance
    Router::get('/employees', [\App\Modules\Employees\EmployeeController::class, 'index']);
    Router::post('/employees', [\App\Modules\Employees\EmployeeController::class, 'store'], [CsrfMiddleware::class]);
    Router::post('/employees/{id}', [\App\Modules\Employees\EmployeeController::class, 'update'], [CsrfMiddleware::class]);
    Router::get('/employees/shifts', [\App\Modules\Employees\EmployeeController::class, 'shifts']);
    Router::post('/employees/shifts', [\App\Modules\Employees\EmployeeController::class, 'storeShift'], [CsrfMiddleware::class]);
    Router::get('/employees/attendance', [\App\Modules\Employees\EmployeeController::class, 'attendance']);
    Router::post('/employees/attendance', [\App\Modules\Employees\EmployeeController::class, 'storeAttendance'], [CsrfMiddleware::class]);

    // Customers (CRM)
    Router::get('/customers', [\App\Modules\Customers\CustomerController::class, 'index']);
    Router::get('/customers/search', [\App\Modules\Customers\CustomerController::class, 'search']);
    Router::post('/customers', [\App\Modules\Customers\CustomerController::class, 'store'], [CsrfMiddleware::class]);
    Router::get('/customers/{id}', [\App\Modules\Customers\CustomerController::class, 'show']);
    Router::post('/customers/{id}/deposit', [\App\Modules\Customers\CustomerController::class, 'topupDeposit'], [CsrfMiddleware::class]);

    // Services & Pricing Matrix
    Router::get('/services', [\App\Modules\Services\ServiceController::class, 'index']);
    Router::post('/services', [\App\Modules\Services\ServiceController::class, 'store'], [CsrfMiddleware::class]);
    Router::post('/services/{id}', [\App\Modules\Services\ServiceController::class, 'update'], [CsrfMiddleware::class]);
    Router::get('/services/pricing', [\App\Modules\Services\ServiceController::class, 'pricing']);
    Router::post('/services/pricing/update', [\App\Modules\Services\ServiceController::class, 'updateBranchPrice'], [CsrfMiddleware::class]);
    Router::post('/services/addons', [\App\Modules\Services\ServiceController::class, 'storeAddon'], [CsrfMiddleware::class]);

    // POS & Orders
    Router::get('/pos', [\App\Modules\Orders\PosController::class, 'index']);
    Router::post('/pos/checkout', [\App\Modules\Orders\PosController::class, 'checkout']);
    Router::get('/orders', [\App\Modules\Orders\OrderController::class, 'index']);
    Router::get('/orders/{id}', [\App\Modules\Orders\OrderController::class, 'show']);
    Router::get('/orders/{id}/print', [\App\Modules\Orders\OrderController::class, 'print']);
    Router::post('/orders/{id}/cancel', [\App\Modules\Orders\OrderController::class, 'cancel'], [CsrfMiddleware::class]);

    // Production & QC
    Router::get('/production', [\App\Modules\Production\ProductionController::class, 'index']);
    Router::post('/production/assign', [\App\Modules\Production\ProductionController::class, 'assign'], [CsrfMiddleware::class]);
    Router::post('/production/start', [\App\Modules\Production\ProductionController::class, 'startStage'], [CsrfMiddleware::class]);
    Router::post('/production/complete', [\App\Modules\Production\ProductionController::class, 'completeStage'], [CsrfMiddleware::class]);
    Router::post('/production/qc', [\App\Modules\Production\ProductionController::class, 'qcCheck'], [CsrfMiddleware::class]);

    // Accounting & Finance
    Router::get('/accounting/cash', [\App\Modules\Accounting\AccountingController::class, 'cash']);
    Router::get('/accounting/expenses', [\App\Modules\Accounting\AccountingController::class, 'expenses']);
    Router::post('/accounting/expenses', [\App\Modules\Accounting\AccountingController::class, 'storeExpense'], [CsrfMiddleware::class]);
    Router::get('/accounting/journals', [\App\Modules\Accounting\AccountingController::class, 'journals']);
    Router::get('/accounting/ledger', [\App\Modules\Accounting\AccountingController::class, 'ledger']);
    Router::get('/accounting/reports', [\App\Modules\Accounting\AccountingController::class, 'reports']);

    // Pickup & Delivery
    Router::get('/delivery', [\App\Modules\Delivery\DeliveryController::class, 'index']);
    Router::post('/delivery/create', [\App\Modules\Delivery\DeliveryController::class, 'store'], [CsrfMiddleware::class]);
    Router::post('/delivery/assign', [\App\Modules\Delivery\DeliveryController::class, 'assignCourier'], [CsrfMiddleware::class]);
    Router::post('/delivery/status', [\App\Modules\Delivery\DeliveryController::class, 'updateStatus'], [CsrfMiddleware::class]);
    Router::post('/delivery/proof', [\App\Modules\Delivery\DeliveryController::class, 'uploadProof'], [CsrfMiddleware::class]);
    Router::get('/delivery/tasks', [\App\Modules\Delivery\DeliveryController::class, 'tasks']);

    // Membership & Vouchers
    Router::get('/membership', [\App\Modules\Membership\MembershipController::class, 'index']);
    Router::post('/membership/tiers/update', [\App\Modules\Membership\MembershipController::class, 'updateTier'], [CsrfMiddleware::class]);
    Router::post('/membership/points/adjust', [\App\Modules\Membership\MembershipController::class, 'adjustPoints'], [CsrfMiddleware::class]);
    Router::get('/vouchers', [\App\Modules\Membership\MembershipController::class, 'vouchers']);
    Router::post('/vouchers/create', [\App\Modules\Membership\MembershipController::class, 'storeVoucher'], [CsrfMiddleware::class]);
    Router::post('/vouchers/toggle', [\App\Modules\Membership\MembershipController::class, 'toggleVoucher'], [CsrfMiddleware::class]);

    // WhatsApp Automation
    Router::get('/whatsapp', [\App\Modules\WhatsApp\WhatsAppController::class, 'index']);
    Router::get('/whatsapp/templates', [\App\Modules\WhatsApp\WhatsAppController::class, 'templates']);
    Router::post('/whatsapp/templates/update', [\App\Modules\WhatsApp\WhatsAppController::class, 'updateTemplate'], [CsrfMiddleware::class]);
    Router::post('/whatsapp/settings', [\App\Modules\WhatsApp\WhatsAppController::class, 'saveSettings'], [CsrfMiddleware::class]);
    Router::post('/whatsapp/send', [\App\Modules\WhatsApp\WhatsAppController::class, 'sendMessage'], [CsrfMiddleware::class]);
    Router::post('/whatsapp/process', [\App\Modules\WhatsApp\WhatsAppController::class, 'processQueue'], [CsrfMiddleware::class]);

    // Inventory & Stock Management
    Router::get('/inventory', [\App\Modules\Inventory\InventoryController::class, 'index']);
    Router::post('/inventory/items', [\App\Modules\Inventory\InventoryController::class, 'storeItem'], [CsrfMiddleware::class]);
    Router::post('/inventory/stock/adjust', [\App\Modules\Inventory\InventoryController::class, 'updateStock'], [CsrfMiddleware::class]);
    Router::get('/inventory/movements', [\App\Modules\Inventory\InventoryController::class, 'movements']);
    Router::get('/inventory/opname', [\App\Modules\Inventory\InventoryController::class, 'opname']);
    Router::post('/inventory/opname/create', [\App\Modules\Inventory\InventoryController::class, 'storeOpname'], [CsrfMiddleware::class]);
    Router::get('/inventory/transfers', [\App\Modules\Inventory\InventoryController::class, 'transfers']);
    Router::post('/inventory/transfers/create', [\App\Modules\Inventory\InventoryController::class, 'storeTransfer'], [CsrfMiddleware::class]);
    Router::post('/inventory/transfers/receive', [\App\Modules\Inventory\InventoryController::class, 'receiveTransfer'], [CsrfMiddleware::class]);
    Router::get('/inventory/suppliers', [\App\Modules\Inventory\InventoryController::class, 'suppliers']);
    Router::post('/inventory/suppliers/create', [\App\Modules\Inventory\InventoryController::class, 'storeSupplier'], [CsrfMiddleware::class]);

    // Machine Fleet & Maintenance
    Router::get('/machines', [\App\Modules\Machines\MachineController::class, 'index']);
    Router::post('/machines/create', [\App\Modules\Machines\MachineController::class, 'storeMachine'], [CsrfMiddleware::class]);
    Router::get('/machines/maintenance', [\App\Modules\Machines\MachineController::class, 'maintenance']);
    Router::post('/machines/maintenance/create', [\App\Modules\Machines\MachineController::class, 'storeMaintenance'], [CsrfMiddleware::class]);
    Router::post('/machines/complete', [\App\Modules\Machines\MachineController::class, 'completeMaintenance'], [CsrfMiddleware::class]);
    Router::post('/machines/fault', [\App\Modules\Machines\MachineController::class, 'reportFault'], [CsrfMiddleware::class]);

    // Reports & Analytics (30 Comprehensive Reports)
    Router::get('/reports', [\App\Modules\Reports\ReportController::class, 'index']);
    Router::get('/reports/{slug}', [\App\Modules\Reports\ReportController::class, 'show']);

    // Settings, Backup & Audit Logs
    Router::get('/settings', [\App\Modules\Settings\SettingController::class, 'index']);
    Router::post('/settings/company', [\App\Modules\Settings\SettingController::class, 'saveCompany'], [CsrfMiddleware::class]);
    Router::get('/settings/receipt', [\App\Modules\Settings\SettingController::class, 'receipt']);
    Router::post('/settings/receipt', [\App\Modules\Settings\SettingController::class, 'saveReceipt'], [CsrfMiddleware::class]);
    Router::get('/settings/backup', [\App\Modules\Settings\SettingController::class, 'backup']);
    Router::post('/settings/backup/create', [\App\Modules\Settings\SettingController::class, 'createBackup'], [CsrfMiddleware::class]);
    Router::get('/settings/backup/archive', [\App\Modules\Settings\SettingController::class, 'downloadFullArchive']);
    Router::get('/settings/backup/export-json', [\App\Modules\Settings\SettingController::class, 'exportJson']);
    Router::get('/settings/backup/download/{file}', [\App\Modules\Settings\SettingController::class, 'downloadBackup']);
    Router::post('/settings/backup/restore', [\App\Modules\Settings\SettingController::class, 'restoreBackup'], [CsrfMiddleware::class]);
    Router::get('/settings/logs', [\App\Modules\Settings\SettingController::class, 'logs']);
});

// Public Tracking Route by Token (Accessible without login)
Router::get('/track/{token}', [\App\Modules\Orders\OrderController::class, 'track']);

// Customer Portal Public Routes
Router::get('/portal/login', [\App\Modules\Portal\PortalController::class, 'login']);
Router::post('/portal/login', [\App\Modules\Portal\PortalController::class, 'doLogin'], [CsrfMiddleware::class]);
Router::get('/portal/register', [\App\Modules\Portal\PortalController::class, 'register']);
Router::post('/portal/register', [\App\Modules\Portal\PortalController::class, 'doRegister'], [CsrfMiddleware::class]);
Router::post('/portal/logout', [\App\Modules\Portal\PortalController::class, 'logout'], [CsrfMiddleware::class]);

// Customer Portal Protected Routes
Router::group(['middleware' => [\App\Middleware\CustomerAuthMiddleware::class]], function() {
    Router::get('/portal', [\App\Modules\Portal\PortalController::class, 'index']);
    Router::get('/portal/my-orders', [\App\Modules\Portal\PortalController::class, 'orders']);
    Router::get('/portal/create-order', [\App\Modules\Portal\PortalController::class, 'createOrder']);
    Router::post('/portal/pickup/create', [\App\Modules\Portal\PortalController::class, 'storePickup'], [CsrfMiddleware::class]);
    Router::post('/portal/pay-qris', [\App\Modules\Portal\PortalController::class, 'payQris'], [CsrfMiddleware::class]);
    Router::post('/portal/review', [\App\Modules\Portal\PortalController::class, 'storeReview'], [CsrfMiddleware::class]);
    Router::get('/portal/profile', [\App\Modules\Portal\PortalController::class, 'profile']);
    Router::post('/portal/profile', [\App\Modules\Portal\PortalController::class, 'updateProfile'], [CsrfMiddleware::class]);
});
