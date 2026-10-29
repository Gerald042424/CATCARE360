<?php

namespace App\Controllers\Public;

use App\Controllers\BaseController;
use App\Models\AdopterModel;
use App\Models\AdopterUserModel;
use App\Models\StaffModel;
use App\Models\VolunteerUserModel;

class AuthController extends BaseController
{
    public function login()
    {
        $email = strtolower(trim((string) $this->request->getPost('email')));
        $password = (string) $this->request->getPost('password');

        if (! filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
            return redirect()->to('/')->with('error', 'Enter a valid email address and password.')->with('auth_modal', 'signin');
        }

        $accounts = $this->accountsForEmail($email);
        if (count($accounts) > 1) {
            log_message('error', 'Duplicate login email found across account roles: {email}', ['email' => $email]);
            return redirect()->to('/')->with('error', 'This email is assigned to more than one account. Please contact the CatCare 360 team.')->with('auth_modal', 'signin');
        }

        $account = $accounts[0] ?? null;
        if (! $account || ! password_verify($password, $account['password_hash'])) {
            return redirect()->to('/')
                ->withInput()
                ->with('login_error', 'Incorrect email or password. Please try again.')
                ->with('auth_modal', 'signin');
        }

        session()->regenerate();
        session()->remove(['adopter_user', 'volunteer_user', 'staff_id', 'staff_name', 'staff_email', 'staff_role']);

        if ($account['type'] === 'staff') {
            session()->set(['staff_id' => $account['id'], 'staff_name' => $account['name'], 'staff_email' => $account['email'], 'staff_role' => $account['role']]);
            return redirect()->to('/admin/dashboard')->with('success', 'Welcome back.');
        }

        if ($account['type'] === 'volunteer') {
            session()->set('volunteer_user', ['id' => $account['id'], 'volunteer_id' => $account['volunteer_id'], 'email' => $account['email']]);
            return redirect()->to('/volunteer')->with('success', 'Welcome to the volunteer portal.');
        }

        session()->set('adopter_user', ['id' => $account['id'], 'adopter_id' => $account['adopter_id'], 'email' => $account['email'], 'name' => trim($account['first_name'] . ' ' . $account['last_name'])]);
        return redirect()->to('/home')->with('success', 'Welcome back.');
    }

    public function adopterLogin()
    {
        $email = strtolower(trim((string) $this->request->getPost('email')));
        $password = (string) $this->request->getPost('password');
        $user = (new AdopterUserModel())->where('email', $email)->first();

        if (! $user || ! password_verify($password, $user['password_hash'])) {
            return redirect()->back()->with('error', 'Invalid adopter email or password.')->with('auth_modal', 'signin');
        }

        session()->regenerate();
        session()->set('adopter_user', [
            'id' => $user['id'],
            'adopter_id' => $user['adopter_id'],
            'email' => $user['email'],
            'name' => trim($user['first_name'] . ' ' . $user['last_name']),
        ]);

        return redirect()->to('/home')->with('success', 'Welcome back.');
    }

    public function sendPasswordResetCode()
    {
        $emailAddress = strtolower(trim((string) $this->request->getPost('email')));
        if (! filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {
            return $this->response->setStatusCode(422)->setJSON(['errors' => ['email' => 'Enter a valid email address.']]);
        }
        $user = (new AdopterUserModel())->where('email', $emailAddress)->first();
        if (! $user) {
            return $this->response->setStatusCode(422)->setJSON(['errors' => ['email' => 'We could not find an adopter account with that email.']]);
        }
        $pending = session('adopter_password_reset');
        if ($pending && ($pending['email'] ?? '') === $emailAddress && ($pending['resend_after'] ?? 0) > time()) {
            return $this->response->setStatusCode(429)->setJSON(['errors' => ['form' => 'Please wait a moment before requesting another code.']]);
        }
        $code = (string) random_int(100000, 999999);
        session()->set('adopter_password_reset', ['email' => $emailAddress, 'code_hash' => password_hash($code, PASSWORD_DEFAULT), 'expires_at' => time() + 600, 'resend_after' => time() + 60, 'attempts' => 0]);
        $email = service('email'); $config = config('Email');
        $email->setFrom($config->fromEmail ?: 'no-reply@catcare360.org', $config->fromName ?: 'CatCare 360');
        $email->setTo($emailAddress); $email->setSubject('Reset your CatCare 360 password'); $email->setMailType('html');
        $email->setMessage($this->passwordResetEmailTemplate($code));
        if (! $email->send()) {
            session()->remove('adopter_password_reset');
            log_message('error', 'Password reset code email could not be sent to {email}.', ['email' => $emailAddress]);
            return $this->response->setStatusCode(503)->setJSON(['errors' => ['form' => 'We could not send the reset code. Please try again shortly.']]);
        }
        return $this->response->setJSON(['message' => 'A six-digit reset code has been sent.', 'email' => $emailAddress]);
    }

    public function resetPassword()
    {
        $pending = session('adopter_password_reset');
        $password = (string) $this->request->getPost('password');
        $confirmPassword = (string) $this->request->getPost('confirm_password');
        if (! $pending || ($pending['expires_at'] ?? 0) < time() || ($pending['verified_at'] ?? 0) < time() - 600) {
            session()->remove('adopter_password_reset');
            return $this->response->setStatusCode(422)->setJSON(['errors' => ['form' => 'Verify your reset code before creating a new password.']]);
        }
        $errors = [];
        if ($passwordError = $this->passwordStrengthError($password)) $errors['password'] = $passwordError;
        if ($password !== $confirmPassword) $errors['confirm_password'] = 'Passwords do not match.';
        if ($errors) return $this->response->setStatusCode(422)->setJSON(['errors' => $errors]);
        (new AdopterUserModel())->where('email', $pending['email'])->set('password_hash', password_hash($password, PASSWORD_DEFAULT))->update();
        session()->remove('adopter_password_reset');
        return $this->response->setJSON(['message' => 'Password updated. You can now sign in.']);
    }

    public function verifyPasswordResetCode()
    {
        $pending = session('adopter_password_reset');
        $code = preg_replace('/\D/', '', (string) $this->request->getPost('code'));
        if (! $pending || ($pending['expires_at'] ?? 0) < time()) {
            session()->remove('adopter_password_reset');
            return $this->response->setStatusCode(422)->setJSON(['errors' => ['form' => 'Your reset session has expired. Please request a new code.']]);
        }
        if (strlen($code) !== 6 || ! password_verify($code, $pending['code_hash'])) {
            $pending['attempts'] = ((int) ($pending['attempts'] ?? 0)) + 1;
            if ($pending['attempts'] >= 5) {
                session()->remove('adopter_password_reset');
                return $this->response->setStatusCode(422)->setJSON(['errors' => ['form' => 'Too many incorrect attempts. Request a new code.']]);
            }
            session()->set('adopter_password_reset', $pending);
            return $this->response->setStatusCode(422)->setJSON(['errors' => ['code' => 'The reset code is incorrect. Please try again.']]);
        }
        $pending['verified_at'] = time();
        session()->set('adopter_password_reset', $pending);
        return $this->response->setJSON(['message' => 'Code verified.']);
    }

    private function passwordResetEmailTemplate(string $code): string
    {
        return '<!doctype html><html><body style="margin:0;background:#f4f7fb;font-family:Arial,sans-serif;padding:28px 16px;color:#172033"><div style="max-width:560px;margin:auto;background:#fff;border-radius:18px;overflow:hidden;box-shadow:0 8px 28px rgba(29,78,216,.1)"><div style="padding:24px 30px;background:#2563eb;color:#fff;font-size:18px;font-weight:bold">🐾 CatCare 360</div><div style="padding:30px"><p style="font-size:12px;font-weight:bold;letter-spacing:1px;text-transform:uppercase;color:#2563eb">Password reset</p><h1 style="font-size:25px;margin:8px 0 14px">Reset your password</h1><p style="color:#4b5563;line-height:1.6">Use this code to set a new password. It expires in 10 minutes.</p><div style="margin:24px 0;padding:20px;text-align:center;border-radius:14px;background:#eff6ff;border:1px solid #bfdbfe"><p style="margin:0 0 9px;font-size:11px;font-weight:bold;letter-spacing:1px;color:#4b6b9d">YOUR RESET CODE</p><div style="font-size:32px;font-weight:bold;letter-spacing:9px;color:#1d4ed8;padding-left:9px">' . $code . '</div></div><p style="font-size:13px;color:#6b7280">Never share this code. If you did not request a reset, you can ignore this email.</p></div></div></body></html>';
    }

    public function register()
    {
        return redirect()->to('/')
            ->with('error', 'Please complete the email verification step to create an account.')
            ->with('auth_modal', 'signup');
    }

    /**
     * Starts the public registration flow. The account is only saved after the
     * email code is successfully verified in verifyRegistrationCode().
     */
    public function sendRegistrationCode()
    {
        $details = $this->registrationDetails();
        $errors = $this->registrationErrors($details);

        if ($errors !== []) {
            return $this->response->setStatusCode(422)->setJSON(['errors' => $errors]);
        }

        $session = session();
        $pending = $session->get('pending_adopter_registration');
        if ($pending && ($pending['email'] ?? '') === $details['email'] && ($pending['resend_after'] ?? 0) > time()) {
            return $this->response->setStatusCode(429)->setJSON([
                'errors' => ['form' => 'Please wait a moment before requesting another code.'],
            ]);
        }

        $code = (string) random_int(100000, 999999);
        $session->set('pending_adopter_registration', [
            'first_name' => $details['first_name'],
            'last_name' => $details['last_name'],
            'email' => $details['email'],
            'password_hash' => password_hash($details['password'], PASSWORD_DEFAULT),
            'code_hash' => password_hash($code, PASSWORD_DEFAULT),
            'expires_at' => time() + 600,
            'resend_after' => time() + 60,
            'attempts' => 0,
        ]);

        $email = service('email');
        $emailConfig = config('Email');
        $email->setFrom($emailConfig->fromEmail ?: 'no-reply@catcare360.org', $emailConfig->fromName ?: 'CatCare 360');
        $email->setTo($details['email']);
        $email->setSubject('Your CatCare 360 verification code');
        $email->setMailType('html');
        $email->setMessage($this->verificationEmailTemplate($details['first_name'], $code));

        if (! $email->send()) {
            log_message('error', 'Registration code email could not be sent to {email}: {error}', [
                'email' => $details['email'],
                'error' => $email->printDebugger(['headers']),
            ]);
            $session->remove('pending_adopter_registration');

            return $this->response->setStatusCode(503)->setJSON([
                'errors' => ['form' => 'We could not send the verification code. Please try again shortly.'],
            ]);
        }

        return $this->response->setJSON([
            'message' => 'A six-digit verification code has been sent to your email.',
            'email' => $details['email'],
        ]);
    }

    public function verifyRegistrationCode()
    {
        $code = preg_replace('/\D/', '', (string) $this->request->getPost('code'));
        $pending = session('pending_adopter_registration');

        if (! $pending) {
            return $this->response->setStatusCode(422)->setJSON(['errors' => ['form' => 'Your verification session has ended. Please start again.']]);
        }

        if (($pending['expires_at'] ?? 0) < time()) {
            session()->remove('pending_adopter_registration');
            return $this->response->setStatusCode(422)->setJSON(['errors' => ['form' => 'That code has expired. Please request a new one.']]);
        }

        if (strlen($code) !== 6 || ! password_verify($code, $pending['code_hash'])) {
            $attempts = ((int) ($pending['attempts'] ?? 0)) + 1;
            if ($attempts >= 5) {
                session()->remove('pending_adopter_registration');
                return $this->response->setStatusCode(422)->setJSON(['errors' => ['form' => 'Too many incorrect attempts. Please request a new code.']]);
            }
            $pending['attempts'] = $attempts;
            session()->set('pending_adopter_registration', $pending);
            return $this->response->setStatusCode(422)->setJSON(['errors' => ['code' => 'The verification code is incorrect. Please try again.']]);
        }

        $userModel = new AdopterUserModel();
        if ($userModel->where('email', $pending['email'])->first()) {
            session()->remove('pending_adopter_registration');
            return $this->response->setStatusCode(422)->setJSON(['errors' => ['form' => 'That email already has an adopter account.']]);
        }

        $adopterModel = new AdopterModel();
        $existingAdopter = $adopterModel->where('email', $pending['email'])->first();
        $adopterId = $existingAdopter['id'] ?? $adopterModel->insert([
            'name' => trim($pending['first_name'] . ' ' . $pending['last_name']),
            'email' => $pending['email'],
            'phone' => '',
            'occupation' => '',
            'housing_type' => 'Apartment',
            'compliance_score' => 0,
            'flag' => 'None',
        ], true);

        $userId = $userModel->insert([
            'adopter_id' => $adopterId,
            'email' => $pending['email'],
            'password_hash' => $pending['password_hash'],
            'first_name' => $pending['first_name'],
            'last_name' => $pending['last_name'],
            'status' => 'active',
        ], true);

        session()->remove('pending_adopter_registration');
        session()->regenerate();
        session()->set('adopter_user', [
            'id' => $userId,
            'adopter_id' => $adopterId,
            'email' => $pending['email'],
            'name' => trim($pending['first_name'] . ' ' . $pending['last_name']),
        ]);
        session()->set('adopter_profile_setup_required', true);

        return $this->response->setJSON(['redirect' => site_url('adopter/home')]);
    }

    private function registrationDetails(): array
    {
        return [
            'first_name' => trim((string) $this->request->getPost('first_name')),
            'last_name' => trim((string) $this->request->getPost('last_name')),
            'email' => strtolower(trim((string) $this->request->getPost('email'))),
            'password' => (string) $this->request->getPost('password'),
            'confirm_password' => (string) $this->request->getPost('confirm_password'),
            'terms' => (string) $this->request->getPost('terms') === '1',
        ];
    }

    private function registrationErrors(array $details): array
    {
        $errors = [];
        if ($details['first_name'] === '') $errors['first_name'] = 'Enter your first name.';
        if ($details['last_name'] === '') $errors['last_name'] = 'Enter your last name.';
        if (! filter_var($details['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Enter a valid email address.';
        if ($passwordError = $this->passwordStrengthError($details['password'])) $errors['password'] = $passwordError;
        if ($details['confirm_password'] === '') $errors['confirm_password'] = 'Confirm your password.';
        elseif ($details['password'] !== $details['confirm_password']) $errors['confirm_password'] = 'Passwords do not match.';
        if (! $details['terms']) $errors['terms'] = 'Read and accept the Terms & Conditions to continue.';
        if ($details['email'] !== '' && $this->accountsForEmail($details['email']) !== []) $errors['email'] = 'That email is already used by another account.';
        return $errors;
    }

    private function accountsForEmail(string $email): array
    {
        $accounts = [];
        if ($adopter = (new AdopterUserModel())->where('email', $email)->first()) $accounts[] = $adopter + ['type' => 'adopter'];
        if ($volunteer = (new VolunteerUserModel())->where('email', $email)->first()) $accounts[] = $volunteer + ['type' => 'volunteer'];
        if ($staff = (new StaffModel())->where('email', $email)->where('is_active', 1)->first()) $accounts[] = $staff + ['type' => 'staff'];
        return $accounts;
    }

    private function passwordStrengthError(string $password): ?string
    {
        if (strlen($password) < 8) return 'Use at least 8 characters.';
        if (! preg_match('/[A-Z]/', $password)) return 'Include at least one uppercase letter.';
        if (! preg_match('/[a-z]/', $password)) return 'Include at least one lowercase letter.';
        if (! preg_match('/\d/', $password)) return 'Include at least one number.';
        if (! preg_match('/[^A-Za-z0-9]/', $password)) return 'Include at least one special character.';
        return null;
    }

    private function verificationEmailTemplate(string $firstName, string $code): string
    {
        $name = htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8');

        return <<<HTML
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>
<body style="margin:0;padding:0;background:#f4f7fb;font-family:Arial,Helvetica,sans-serif;color:#172033;">
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f4f7fb;padding:32px 16px;">
    <tr><td align="center">
      <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:560px;background:#ffffff;border-radius:20px;overflow:hidden;box-shadow:0 8px 28px rgba(29,78,216,.10);">
        <tr><td style="background:linear-gradient(135deg,#2563eb,#4338ca);padding:30px 34px;color:#ffffff;">
          <table role="presentation" cellspacing="0" cellpadding="0"><tr><td style="width:42px;height:42px;border-radius:12px;background:rgba(255,255,255,.18);text-align:center;font-size:22px;">🐾</td><td style="padding-left:12px;"><div style="font-size:18px;font-weight:700;">CatCare 360</div><div style="font-size:12px;opacity:.8;margin-top:3px;">McKinley West Cats</div></td></tr></table>
        </td></tr>
        <tr><td style="padding:34px;">
          <div style="font-size:12px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:#2563eb;">Secure verification</div>
          <h1 style="margin:10px 0 10px;font-size:26px;line-height:1.25;color:#172033;">Almost there, {$name}!</h1>
          <p style="margin:0;font-size:15px;line-height:1.65;color:#5b6475;">Use this one-time verification code to finish creating your CatCare 360 adopter account.</p>
          <div style="margin:28px 0;background:#eff6ff;border:1px solid #bfdbfe;border-radius:16px;padding:22px;text-align:center;">
            <div style="font-size:11px;font-weight:700;letter-spacing:1.3px;text-transform:uppercase;color:#4b6b9d;margin-bottom:10px;">Your verification code</div>
            <div style="font-size:32px;line-height:1;font-weight:700;letter-spacing:9px;color:#1d4ed8;padding-left:9px;">{$code}</div>
          </div>
          <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f8fafc;border-radius:12px;"><tr><td style="padding:14px 16px;font-size:13px;line-height:1.5;color:#64748b;">⏱ <strong style="color:#334155;">Expires in 10 minutes.</strong> For your security, never share this code with anyone.</td></tr></table>
          <p style="margin:24px 0 0;font-size:13px;line-height:1.6;color:#7b8494;">If you did not request an account, you can safely ignore this email.</p>
        </td></tr>
        <tr><td style="padding:20px 34px;background:#f8fafc;border-top:1px solid #e8edf5;text-align:center;font-size:12px;line-height:1.5;color:#8a94a6;">© 2026 CatCare 360 · McKinley West Cats</td></tr>
      </table>
    </td></tr>
  </table>
</body>
</html>
HTML;
    }

    public function volunteerLogin()
    {
        $email = strtolower(trim((string) $this->request->getPost('email')));
        $password = (string) $this->request->getPost('password');
        $user = (new VolunteerUserModel())->where('email', $email)->first();

        if (! $user || ! password_verify($password, $user['password_hash'])) {
            return redirect()->back()->with('error', 'Invalid volunteer email or password.')->with('auth_modal', 'signin');
        }

        session()->regenerate();
        session()->set('volunteer_user', [
            'id' => $user['id'],
            'volunteer_id' => $user['volunteer_id'],
            'email' => $user['email'],
        ]);

        return redirect()->to('/volunteer')->with('success', 'Welcome to the volunteer portal.');
    }

    public function logout()
    {
        session()->remove([
            'adopter_user', 'volunteer_user', 'staff_id', 'staff_name', 'staff_email', 'staff_role',
            'auth_session_identity', 'auth_session_started_at', 'auth_last_activity_at',
        ]);

        return redirect()->to('/')->with('success', 'Signed out successfully.');
    }
}
