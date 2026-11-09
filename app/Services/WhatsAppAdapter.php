<?php

namespace App\Services;

use App\Core\Database;

class WhatsAppAdapter
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Enqueue a WhatsApp notification using a predefined template.
     */
    public function enqueue(int $companyId, ?int $branchId, string $phoneNumber, string $recipientName, string $triggerEvent, array $variables = [], ?string $refType = null, ?int $refId = null): ?int
    {
        // Clean phone number (format Indonesian 08xx -> 628xx)
        $cleanPhone = preg_replace('/[^0-9]/', '', $phoneNumber);
        if (str_starts_with($cleanPhone, '0')) {
            $cleanPhone = '62' . substr($cleanPhone, 1);
        }

        // Fetch template
        $template = $this->db->fetch(
            "SELECT * FROM whatsapp_templates WHERE company_id = :c AND trigger_event = :t AND is_active = 1",
            [':c' => $companyId, ':t' => $triggerEvent]
        );

        if (!$template) {
            return null; // Template not active or not found
        }

        $message = $template['message_template'];
        foreach ($variables as $key => $val) {
            $message = str_replace("{{$key}}", (string)$val, $message);
        }

        $now = date('Y-m-d H:i:s');
        $queueId = $this->db->insert('whatsapp_queue', [
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'phone_number' => $cleanPhone,
            'recipient_name' => $recipientName,
            'message' => $message,
            'trigger_event' => $triggerEvent,
            'reference_type' => $refType,
            'reference_id' => $refId,
            'status' => 'pending',
            'attempts' => 0,
            'max_attempts' => 3,
            'scheduled_at' => $now,
            'created_at' => $now,
        ]);

        return $queueId;
    }

    /**
     * Process pending messages in the queue (invoked by console or cron).
     */
    public function processQueue(int $limit = 10): int
    {
        $pending = $this->db->fetchAll(
            "SELECT q.*, ws.provider, ws.api_url, ws.api_key, ws.sender_number
             FROM whatsapp_queue q
             LEFT JOIN whatsapp_settings ws ON q.company_id = ws.company_id
             WHERE q.status = 'pending' AND q.scheduled_at <= :now
             ORDER BY q.id ASC
             LIMIT " . (int)$limit,
            [':now' => date('Y-m-d H:i:s')]
        );

        $processed = 0;
        foreach ($pending as $item) {
            $provider = $item['provider'] ?? 'mock';
            $success = false;
            $responsePayload = '';

            if ($provider === 'mock') {
                // In mock mode (safe development/shared hosting offline testing)
                $success = true;
                $responsePayload = json_encode(['status' => 'mock_sent', 'message_id' => 'MOCK-' . uniqid()]);
            } else {
                // Real Gateway Dispatch (Fonnte / Wablas / WhatsApp Cloud API)
                $apiUrl = $item['api_url'] ?: 'https://api.fonnte.com/send';
                $apiKey = $item['api_key'] ?? '';

                $ch = curl_init();
                curl_setopt_array($ch, [
                    CURLOPT_URL => $apiUrl,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => http_build_query([
                        'target' => $item['phone_number'],
                        'message' => $item['message'],
                    ]),
                    CURLOPT_HTTPHEADER => [
                        "Authorization: {$apiKey}",
                    ],
                    CURLOPT_TIMEOUT => 10,
                ]);

                $result = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                $responsePayload = (string)$result;
                $success = ($httpCode >= 200 && $httpCode < 300);
            }

            $now = date('Y-m-d H:i:s');
            if ($success) {
                $this->db->update('whatsapp_queue', [
                    'status' => 'sent',
                    'sent_at' => $now,
                    'attempts' => $item['attempts'] + 1,
                ], 'id = :id', [':id' => $item['id']]);

                $this->db->insert('whatsapp_logs', [
                    'company_id' => $item['company_id'],
                    'queue_id' => $item['id'],
                    'phone_number' => $item['phone_number'],
                    'message' => $item['message'],
                    'response_payload' => $responsePayload,
                    'status' => 'sent',
                    'created_at' => $now,
                ]);
            } else {
                $newAttempts = $item['attempts'] + 1;
                $newStatus = ($newAttempts >= $item['max_attempts']) ? 'failed' : 'pending';

                $this->db->update('whatsapp_queue', [
                    'status' => $newStatus,
                    'attempts' => $newAttempts,
                    'last_error' => substr($responsePayload, 0, 255),
                ], 'id = :id', [':id' => $item['id']]);

                $this->db->insert('whatsapp_logs', [
                    'company_id' => $item['company_id'],
                    'queue_id' => $item['id'],
                    'phone_number' => $item['phone_number'],
                    'message' => $item['message'],
                    'response_payload' => $responsePayload,
                    'status' => 'failed',
                    'created_at' => $now,
                ]);
            }

            $processed++;
        }

        return $processed;
    }
}
