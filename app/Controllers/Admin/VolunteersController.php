<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\AdopterUserModel;
use App\Models\StaffModel;
use App\Models\VolunteerModel;
use App\Models\VolunteerUserModel;
use App\Services\CloudStorageService;

class VolunteersController extends BaseController
{
    public function index(): string
    {
        return view('admin/volunteers/index', [
            'title' => 'Volunteer Management',
            'volunteers' => (new VolunteerModel())->orderBy('name')->findAll(),
        ]);
    }

    public function form(?int $id = null)
    {
        $model = new VolunteerModel();
        $volunteer = $id ? $model->find($id) : null;

        if ($this->request->getMethod() === 'POST') {
            $days = array_values(array_intersect(
                ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
                (array) $this->request->getPost('availability_days')
            ));
            $data = $this->request->getPost(['name', 'email', 'phone', 'location', 'status', 'notes', 'joined_date', 'availability_start_time', 'availability_end_time']);
            $data['name'] = trim((string) $data['name']);
            $data['email'] = strtolower(trim((string) $data['email']));
            // Every profile on this page is a volunteer. Keep the legacy column
            // populated for older records/views without exposing a role selector.
            $data['role'] = 'Volunteer';
            $data['availability'] = implode(',', $days);
            $data['availability_start_time'] = $data['availability_start_time'] ?: null;
            $data['availability_end_time'] = $data['availability_end_time'] ?: null;

            if ($data['status'] === 'Active' && ($days === [] || ! $data['availability_start_time'] || ! $data['availability_end_time'])) {
                return redirect()->back()->withInput()->with('error', 'Set at least one available day and an available start and end time for an active volunteer.');
            }
            if ($data['availability_start_time'] && $data['availability_end_time'] && $data['availability_start_time'] >= $data['availability_end_time']) {
                return redirect()->back()->withInput()->with('error', 'Availability end time must be later than the start time.');
            }

            if ($data['name'] === '' || ! filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                return redirect()->back()->withInput()->with('error', 'Enter the volunteer name and a valid email address.');
            }

            $photo = $this->request->getFile('photo');
            if ($photo && $photo->getError() !== UPLOAD_ERR_NO_FILE) {
                if (! $photo->isValid() || $photo->hasMoved() || ! in_array($photo->getMimeType(), ['image/jpeg', 'image/png', 'image/webp'], true) || $photo->getSize() > 5 * 1024 * 1024) {
                    return redirect()->back()->withInput()->with('error', 'Upload or capture a JPG, PNG, or WebP photo up to 5 MB.');
                }

                try {
                    $data['image_url'] = (new CloudStorageService())->store($photo, 'volunteers');
                } catch (\Throwable $exception) {
                    log_message('error', 'Volunteer photo upload failed: {message}', ['message' => $exception->getMessage()]);
                    return redirect()->back()->withInput()->with('error', 'Unable to save the volunteer photo. Please try again.');
                }
            }

            $users = new VolunteerUserModel();
            $existingUser = $id ? $users->where('volunteer_id', $id)->first() : null;
            $emailOwner = $users->where('email', $data['email'])->first();
            if ($emailOwner && (! $existingUser || (int) $emailOwner['id'] !== (int) $existingUser['id'])) {
                return redirect()->back()->withInput()->with('error', 'That email address is already used by another account.');
            }
            if ((new AdopterUserModel())->where('email', $data['email'])->first() || (new StaffModel())->where('email', $data['email'])->first()) {
                return redirect()->back()->withInput()->with('error', 'That email address is already used by another account.');
            }

            if ($id) {
                $model->update($id, $data);
                if ($existingUser) {
                    $users->update($existingUser['id'], ['email' => $data['email']]);
                }

                return redirect()->to('/admin/volunteers')->with('success', 'Volunteer saved.');
            }

            $volunteerId = (int) $model->insert($data, true);
            $temporaryPassword = 'Vol!' . bin2hex(random_bytes(4));
            $users->insert([
                'volunteer_id' => $volunteerId,
                'email' => $data['email'],
                'password_hash' => password_hash($temporaryPassword, PASSWORD_DEFAULT),
            ]);

            $emailSent = $this->sendWelcomeEmail($data['name'], $data['email'], $temporaryPassword);

            return redirect()->to('/admin/volunteers')->with(
                $emailSent ? 'success' : 'error',
                $emailSent
                    ? 'Volunteer saved and login credentials were emailed.'
                    : 'Volunteer was saved, but the credentials email could not be sent. Check the email settings and resend the credentials manually.'
            );
        }

        return view('admin/volunteers/form', ['title' => $id ? 'Edit Volunteer' : 'Add Volunteer', 'volunteer' => $volunteer]);
    }

    public function detail(int $id)
    {
        return $this->form($id);
    }

    public function delete(int $id)
    {
        (new VolunteerModel())->delete($id);

        return redirect()->to('/admin/volunteers')->with('success', 'Volunteer deleted.');
    }

    private function sendWelcomeEmail(string $name, string $emailAddress, string $temporaryPassword): bool
    {
        $firstName = trim(explode(' ', $name)[0]) ?: 'Volunteer';
        $email = service('email');
        $email->setTo($emailAddress);
        $email->setSubject('Your CatCare 360 volunteer login details');
        $email->setMailType('html');
        $email->setMessage('<!doctype html><html><body style="margin:0;padding:24px;background:#f4f7fb;font-family:Arial,sans-serif;color:#172033"><div style="max-width:560px;margin:auto;background:#ffffff;border-radius:18px;overflow:hidden;box-shadow:0 8px 28px rgba(29,78,216,.12)"><div style="padding:24px 30px;background:#2563eb;color:#fff;font-size:18px;font-weight:bold">🐾 CatCare 360</div><div style="padding:30px"><p style="font-size:12px;font-weight:bold;letter-spacing:1px;text-transform:uppercase;color:#2563eb">Volunteer welcome</p><h1 style="font-size:25px;margin:8px 0 14px">Hi ' . esc($firstName) . '!</h1><p style="color:#4b5563;line-height:1.6">Welcome to CatCare 360. Your volunteer account is ready. Use these details in the CatCare 360 Sign In form.</p><div style="margin:24px 0;padding:20px;border-radius:14px;background:#eff6ff;border:1px solid #bfdbfe"><p style="margin:0 0 12px;font-size:12px;font-weight:bold;color:#4b6b9d">YOUR LOGIN DETAILS</p><p style="margin:0 0 10px;color:#172033"><strong>Email:</strong> ' . esc($emailAddress) . '</p><p style="margin:0;color:#172033"><strong>Temporary password:</strong> <span style="font-size:20px;letter-spacing:3px;font-weight:bold;color:#1d4ed8">' . esc($temporaryPassword) . '</span></p></div><p style="font-size:13px;color:#6b7280;line-height:1.6">Keep this password private. If you have questions, please message the CatCare 360 team.</p></div></div></body></html>');

        if (! $email->send()) {
            log_message('error', 'Volunteer credentials email could not be sent to {email}.', ['email' => $emailAddress]);
            return false;
        }

        return true;
    }
}
