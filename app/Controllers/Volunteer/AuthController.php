<?php

namespace App\Controllers\Volunteer;

use App\Controllers\BaseController;
use App\Models\VolunteerUserModel;

class AuthController extends BaseController
{
    public function loginPage()
    {
        if (session('volunteer_user')) return redirect()->to('/volunteer');
        return redirect()->to('/')->with('auth_modal', 'signin');
    }

    public function login()
    {
        if (session('volunteer_user')) {
            return redirect()->to('/volunteer');
        }

        if ($this->request->getMethod() === 'POST') {
            $user = (new VolunteerUserModel())->where('email', $this->request->getPost('email'))->first();
            $code = (string) $this->request->getPost('access_code');

            if ($user && hash_equals((string) $user['access_code'], $code)) {
                session()->regenerate();
                session()->set('volunteer_user', [
                    'id' => $user['id'],
                    'volunteer_id' => $user['volunteer_id'],
                    'email' => $user['email'],
                ]);

                return redirect()->to('/volunteer');
            }

            return redirect()->back()->withInput()->with('error', 'Invalid email or access code.');
        }

        return redirect()->to('/volunteer/login');
    }

    public function logout()
    {
        session()->remove('volunteer_user');

        return redirect()->to('/')->with('success', 'Signed out successfully.');
    }

    public function updateSettings()
    {
        $user = session('volunteer_user');
        if (! $user) return redirect()->to('/volunteer/login');
        (new \App\Models\VolunteerModel())->update($user['volunteer_id'], ['notif_email'=>(int)(bool)$this->request->getPost('notif_email'), 'privacy_mode'=>(int)(bool)$this->request->getPost('privacy_mode')]);
        return redirect()->back()->with('success', 'Settings saved.');
    }
}
