<?php

namespace App\Core;

abstract class Controller
{
    protected function db(): Database
    {
        return Database::getInstance();
    }

    protected function user(): ?array
    {
        return Auth::user();
    }

    protected function branchId(): ?int
    {
        return Auth::getActiveBranchId();
    }

    protected function companyId(): int
    {
        $user = $this->user();
        if ($user && !empty($user['company_id'])) {
            return (int)$user['company_id'];
        }
        return 1; // Default company fallback
    }

    protected function view(string $view, array $data = [], ?string $layout = 'layouts/admin'): void
    {
        View::render($view, $data, $layout);
    }

    protected function json(mixed $data, int $status = 200): void
    {
        Response::json($data, $status);
    }

    protected function success(string $message = 'Berhasil', mixed $data = null, int $status = 200): void
    {
        Response::success($message, $data, $status);
    }

    protected function error(string $message = 'Terjadi kesalahan', mixed $errors = null, int $status = 400): void
    {
        Response::error($message, $errors, $status);
    }

    protected function redirect(string $url, ?string $flashMessage = null, string $flashType = 'success'): void
    {
        if ($flashMessage !== null) {
            Session::flash($flashType, $flashMessage);
        }
        Response::redirect($url);
    }

    protected function back(?string $flashMessage = null, string $flashType = 'error'): void
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? url('/dashboard');
        $this->redirect($referer, $flashMessage, $flashType);
    }

    protected function validate(array $data, array $rules): array
    {
        $validator = Validator::make($data, $rules);
        if ($validator->fails()) {
            if (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')) {
                Response::error('Validasi gagal', $validator->errors(), 422);
            }
            Session::flash('error', $validator->firstError() ?? 'Input formulir tidak valid.');
            $this->back();
        }
        return $data;
    }

    protected function logActivity(string $action, string $entityType, ?int $entityId, string $description): void
    {
        try {
            $user = $this->user();
            $this->db()->insert('activity_logs', [
                'company_id' => $this->companyId(),
                'branch_id' => $this->branchId(),
                'user_id' => $user['id'] ?? null,
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'description' => $description,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            // Fail silently so logging never breaks core flow
        }
    }
}
