<?php
namespace Inventory;

use Core\Controller;
use Core\Database;

class UserController extends Controller {
    public function index(): void {
        $this->requireRole('admin');
        $users = Database::table('users')
            ->select('user_id, name, email, role, is_active, created_at')
            ->orderBy('name', 'ASC')
            ->get();
        $this->success('Users retrieved', ['data' => $users]);
    }

    public function store(): void {
        $this->requireRole('admin');
        $data = $this->getJsonInput();
        $errors = $this->validateRequired($data, ['name', 'email', 'password', 'role']);
        if (!empty($errors)) $this->validationError('Validation failed', $errors);

        $existing = Database::table('users')->where('email', $data['email'])->first();
        if ($existing) $this->validationError('Email already exists', ['email' => 'This email is already in use']);

        $id = Database::table('users')->insert([
            'name'           => $data['name'],
            'email'          => $data['email'],
            'password_hash'  => password_hash($data['password'], PASSWORD_BCRYPT),
            'role'           => $data['role'],
            'is_active'      => 1,
            'created_at'     => date('Y-m-d H:i:s'),
        ]);

        $this->created('User created', ['user_id' => $id]);
    }

    public function update(int $id): void {
        $this->requireRole('admin');
        $data = $this->getJsonInput();

        $user = Database::table('users')->where('user_id', $id)->first();
        if (!$user) $this->notFound('User not found');

        $updateData = [];
        if (isset($data['name'])) $updateData['name'] = $data['name'];
        if (isset($data['email'])) $updateData['email'] = $data['email'];
        if (isset($data['role'])) $updateData['role'] = $data['role'];
        if (isset($data['is_active'])) $updateData['is_active'] = $data['is_active'] ? 1 : 0;
        if (!empty($data['password'])) $updateData['password_hash'] = password_hash($data['password'], PASSWORD_BCRYPT);

        if (!empty($updateData)) {
            Database::table('users')->where('user_id', $id)->update($updateData);
        }

        $this->success('User updated');
    }
}
