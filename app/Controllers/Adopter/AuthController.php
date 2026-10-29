<?php

namespace App\Controllers\Adopter;

use App\Controllers\BaseController;
use App\Models\AdopterModel;
use App\Models\AdopterUserModel;

class AuthController extends BaseController
{
    public function loginForm()
    {
        return redirect()->to('/')->with('auth_modal', 'signin')->with('signin_type', 'adopter');
    }

    public function registerForm()
    {
        return redirect()->to('/')->with('auth_modal', 'signup');
    }

    public function login()
    {
        $email = strtolower(trim((string) $this->request->getPost('email')));
        $password = (string) $this->request->getPost('password');
        $user = (new AdopterUserModel())->where('email', $email)->first();

        if (! $user || ! password_verify($password, $user['password_hash'])) {
            return redirect()->back()->with('error', 'Invalid email or password.');
        }

        $adopter = (new AdopterModel())->find($user['adopter_id']);

        session()->regenerate();
        session()->set('adopter_user', [
            'id' => $user['id'],
            'adopter_id' => $user['adopter_id'],
            'email' => $user['email'],
            'name' => trim($user['first_name'] . ' ' . $user['last_name']),
            'status' => $adopter['status'] ?? 'pending',
        ]);

        return redirect()->to('/adopter/home')->with('success', 'Welcome back.');
    }

    public function register()
    {
        $firstName = trim((string) $this->request->getPost('first_name'));
        $lastName = trim((string) $this->request->getPost('last_name'));
        $email = strtolower(trim((string) $this->request->getPost('email')));
        $password = (string) $this->request->getPost('password');
        $confirmPassword = (string) $this->request->getPost('confirm_password');
        $terms = (bool) $this->request->getPost('terms');

        if (! $terms) {
            return redirect()->back()->withInput()->with('error', 'Please accept the terms before continuing.');
        }

        if (! $firstName || ! $lastName || ! $email || ! $password || ! $confirmPassword) {
            return redirect()->back()->withInput()->with('error', 'Please fill out all required fields.');
        }

        if ($password !== $confirmPassword) {
            return redirect()->back()->withInput()->with('error', 'Passwords do not match.');
        }

        if (strlen($password) < 8) {
            return redirect()->back()->withInput()->with('error', 'Password must be at least 8 characters.');
        }

        $userModel = new AdopterUserModel();
        if ($userModel->where('email', $email)->first()) {
            return redirect()->back()->withInput()->with('error', 'That email is already in use.');
        }

        $adopterModel = new AdopterModel();
        $existingAdopter = $adopterModel->where('email', $email)->first();

        $adopterId = $existingAdopter['id'] ?? $adopterModel->insert([
            'application_id' => 'AC' . time() . rand(10, 99),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'name' => trim($firstName . ' ' . $lastName),
            'email' => $email,
            'status' => 'pending',
        ], true);

        $userModel->insert([
            'adopter_id' => $adopterId,
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'status' => 'active',
        ]);

        session()->regenerate();
        session()->set('adopter_user', [
            'id' => $userModel->getInsertID(),
            'adopter_id' => $adopterId,
            'email' => $email,
            'name' => trim($firstName . ' ' . $lastName),
            'status' => 'active',
        ]);

        return redirect()->to('/adopter/home')->with('success', 'Account created.');
    }

    public function logout()
    {
        $inactive = $this->request->getGet('reason') === 'inactive';
        session()->remove('adopter_user');
        session()->destroy();

        return redirect()->to('/')->with(
            $inactive ? 'error' : 'success',
            $inactive ? 'You were signed out for your account safety after being inactive.' : 'You have been logged out.'
        );
    }
}
