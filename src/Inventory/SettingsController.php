<?php
namespace Inventory;

use Core\Controller;
use Core\Database;

class SettingsController extends Controller {
    public function index(): void {
        $this->requireAuth();
        $settings = Database::table('settings')->orderBy('category', 'ASC')->orderBy('setting_key', 'ASC')->get();
        $grouped = [];
        foreach ($settings as $s) {
            $grouped[$s['category'] ?? 'general'][] = $s;
        }
        $this->success('Settings retrieved', ['data' => $grouped]);
    }

    public function update(): void {
        $this->requireRole('admin');
        $data = $this->getJsonInput();
        if (!isset($data['settings']) || !is_array($data['settings'])) {
            $this->validationError('Settings array is required');
        }

        foreach ($data['settings'] as $key => $value) {
            setSetting($key, $value);
        }

        $this->success('Settings updated');
    }
}
