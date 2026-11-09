<?php

declare(strict_types=1);

namespace App\Modules\WhatsApp;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Permission;
use App\Core\Validator;
use App\Services\WhatsAppAdapter;

class WhatsAppController extends Controller
{
    public function index(): void
    {
        Permission::abortUnless('manage.settings');

        $companyId = Auth::companyId() ?: 1;

        $settings = $this->db()->fetch(
            "SELECT * FROM whatsapp_settings WHERE company_id = :c",
            [':c' => $companyId]
        );

        if (!$settings) {
            $now = date('Y-m-d H:i:s');
            $this->db()->insert('whatsapp_settings', [
                'company_id' => $companyId,
                'provider' => 'mock',
                'api_url' => 'https://api.fonnte.com/send',
                'api_key' => '',
                'sender_number' => '081234567890',
                'is_active' => 1,
                'updated_at' => $now,
            ]);
            $settings = $this->db()->fetch("SELECT * FROM whatsapp_settings WHERE company_id = :c", [':c' => $companyId]);
        }

        $queue = $this->db()->fetchAll(
            "SELECT q.*, b.name AS branch_name
             FROM whatsapp_queue q
             LEFT JOIN branches b ON q.branch_id = b.id
             ORDER BY q.id DESC
             LIMIT 50"
        );

        $stats = [
            'total' => (int)$this->db()->fetchColumn("SELECT COUNT(*) FROM whatsapp_queue"),
            'pending' => (int)$this->db()->fetchColumn("SELECT COUNT(*) FROM whatsapp_queue WHERE status = 'pending'"),
            'sent' => (int)$this->db()->fetchColumn("SELECT COUNT(*) FROM whatsapp_queue WHERE status = 'sent'"),
            'failed' => (int)$this->db()->fetchColumn("SELECT COUNT(*) FROM whatsapp_queue WHERE status = 'failed'"),
        ];

        $customers = $this->db()->fetchAll("SELECT id, name, whatsapp, whatsapp AS phone FROM customers WHERE status = 'active' ORDER BY name ASC LIMIT 100");

        $this->view('whatsapp.index', [
            'title' => 'Integrasi & Antrean WhatsApp',
            'activeMenu' => 'whatsapp_queue',
            'settings' => $settings,
            'queue' => $queue,
            'stats' => $stats,
            'customers' => $customers,
        ]);
    }

    public function templates(): void
    {
        Permission::abortUnless('manage.settings');

        $companyId = Auth::companyId() ?: 1;
        $templates = $this->db()->fetchAll(
            "SELECT * FROM whatsapp_templates WHERE company_id = :c ORDER BY id ASC",
            [':c' => $companyId]
        );

        $this->view('whatsapp.templates', [
            'title' => 'Template Pesan WhatsApp',
            'activeMenu' => 'whatsapp_templates',
            'templates' => $templates,
        ]);
    }

    public function updateTemplate(): void
    {
        Permission::abortUnless('manage.settings');

        $templateId = (int)($_POST['template_id'] ?? 0);
        $message = trim($_POST['message_template'] ?? '');
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if ($templateId <= 0 || empty($message)) {
            $this->back('Template dan isi pesan wajib diisi.', 'error');
        }

        $this->db()->update('whatsapp_templates', [
            'message_template' => $message,
            'is_active' => $isActive,
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = :id', [':id' => $templateId]);

        $this->back('Template pesan WhatsApp berhasil diperbarui.', 'success');
    }

    public function saveSettings(): void
    {
        Permission::abortUnless('manage.settings');

        $companyId = Auth::companyId() ?: 1;
        $provider = in_array($_POST['provider'], ['official', 'wablas', 'fonnte', 'mock']) ? $_POST['provider'] : 'mock';
        $apiUrl = trim($_POST['api_url'] ?? '');
        $apiKey = trim($_POST['api_key'] ?? '');
        $sender = trim($_POST['sender_number'] ?? '');
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        $this->db()->update('whatsapp_settings', [
            'provider' => $provider,
            'api_url' => $apiUrl,
            'api_key' => $apiKey,
            'sender_number' => $sender,
            'is_active' => $isActive,
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'company_id = :c', [':c' => $companyId]);

        $this->back('Pengaturan gateway WhatsApp berhasil disimpan.', 'success');
    }

    public function sendMessage(): void
    {
        Permission::abortUnless('manage.settings');

        $phone = trim($_POST['phone_number'] ?? '');
        $name = trim($_POST['recipient_name'] ?? 'Pelanggan');
        $message = trim($_POST['message'] ?? '');

        if (empty($phone) || empty($message)) {
            $this->back('Nomor tujuan WhatsApp dan isi pesan wajib diisi.', 'error');
        }

        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($cleanPhone, '0')) {
            $cleanPhone = '62' . substr($cleanPhone, 1);
        }

        $companyId = Auth::companyId() ?: 1;
        $now = date('Y-m-d H:i:s');

        $this->db()->insert('whatsapp_queue', [
            'company_id' => $companyId,
            'branch_id' => Auth::getActiveBranchId(),
            'phone_number' => $cleanPhone,
            'recipient_name' => $name,
            'message' => $message,
            'trigger_event' => 'manual',
            'reference_type' => 'manual',
            'reference_id' => null,
            'status' => 'pending',
            'attempts' => 0,
            'max_attempts' => 3,
            'scheduled_at' => $now,
            'created_at' => $now,
        ]);

        // Process queue immediately
        $adapter = new WhatsAppAdapter();
        $adapter->processQueue(50);

        $this->back("Pesan WhatsApp ke {$cleanPhone} berhasil dikirim.", 'success');
    }

    public function processQueue(): void
    {
        Permission::abortUnless('manage.settings');

        $adapter = new WhatsAppAdapter();
        $count = $adapter->processQueue(20);

        $this->back("Berhasil memproses {$count} pesan antrean WhatsApp.", 'success');
    }
}
