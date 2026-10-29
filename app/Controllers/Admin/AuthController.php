<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\StaffModel;

class AuthController extends BaseController
{
    public function loginForm()
    {
        if (session()->get('staff_id')) return redirect()->to('/admin/dashboard');
        return redirect()->to('/')->with('auth_modal', 'signin');
    }

    public function login()
    {
        $email = strtolower(trim((string) $this->request->getPost('email')));
        $password = (string) $this->request->getPost('password');
        $staff = (new StaffModel())->where('email', $email)->where('is_active', 1)->first();

        if (! $staff || ! password_verify($password, $staff['password_hash'])) {
            return redirect()->back()->withInput()->with('error', 'Invalid email or password');
        }

        session()->regenerate();
        session()->set([
            'staff_id' => $staff['id'],
            'staff_name' => $staff['name'],
            'staff_email' => $staff['email'],
            'staff_role' => $staff['role'],
        ]);

        return redirect()->to('/admin/dashboard');
    }

    public function logout()
    {
        $inactive = $this->request->getGet('reason') === 'inactive';
        session()->remove([
            'staff_id', 'staff_name', 'staff_email', 'staff_role',
            'auth_session_identity', 'auth_session_started_at', 'auth_last_activity_at',
        ]);

        return redirect()->to('/admin/login')->with(
            $inactive ? 'error' : 'success',
            $inactive ? 'You were signed out for your account safety after being inactive.' : 'You have been logged out.'
        );
    }
}
