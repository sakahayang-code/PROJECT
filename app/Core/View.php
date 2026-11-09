<?php

namespace App\Core;

class View
{
    public static function render(string $view, array $data = [], ?string $layout = 'layouts/admin'): void
    {
        $baseViewsDir = dirname(__DIR__, 2) . '/resources/views';
        $viewFile = $baseViewsDir . '/' . str_replace('.', '/', $view) . '.php';

        if (!file_exists($viewFile)) {
            throw new \Exception("View not found: {$view} (looked at {$viewFile})");
        }

        // Global variables for views
        $appConfig = require dirname(__DIR__, 2) . '/config/app.php';
        $data['appConfig'] = $appConfig;
        $data['currentUser'] = Auth::user();
        $data['currentCustomer'] = Auth::customer();
        $data['csrfToken'] = Session::getCsrfToken();
        $data['flashSuccess'] = Session::flash('success');
        $data['flashError'] = Session::flash('error');
        $data['flashWarning'] = Session::flash('warning');
        $data['flashInfo'] = Session::flash('info');

        // Extract variables to local scope
        extract($data);

        if ($layout === null) {
            require $viewFile;
            return;
        }

        // Buffer view content
        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        // Render layout
        $layoutFile = $baseViewsDir . '/' . str_replace('.', '/', $layout) . '.php';
        if (!file_exists($layoutFile)) {
            throw new \Exception("Layout not found: {$layout}");
        }

        require $layoutFile;
    }
}
