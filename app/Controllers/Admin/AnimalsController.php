<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\AnimalModel;
use App\Models\AnimalPhotoModel;
use App\Models\AnimalJourneyEventModel;
use App\Models\AdopterUserModel;

class AnimalsController extends BaseController
{
    public function index(): string
    {
        $model = new AnimalModel();
        $search = trim((string) $this->request->getGet('search'));
        $filters = [
            'status' => trim((string) $this->request->getGet('status')),
            'health' => trim((string) $this->request->getGet('health')),
            'gender' => trim((string) $this->request->getGet('gender')),
            'breed' => trim((string) $this->request->getGet('breed')),
        ];
        if ($search !== '') {
            $model->groupStart()->like('name', $search)->orLike('breed', $search)->groupEnd();
        }
        foreach (['status', 'health', 'gender', 'breed'] as $field) {
            if ($filters[$field] !== '') $model->where($field, $filters[$field]);
        }
        $breeds = (new AnimalModel())->select('breed')->where('breed IS NOT NULL', null, false)->where('breed !=', '')->groupBy('breed')->orderBy('breed', 'ASC')->findAll();

        return view('admin/animals/index', [
            'title' => 'Cat Management',
            'animals' => $model->orderBy('created_at', 'DESC')->findAll(),
            'search' => $search,
            'filters' => $filters,
            'breeds' => array_values(array_unique(array_column($breeds, 'breed'))),
        ]);
    }

    public function form(?int $id = null)
    {
        $model = new AnimalModel();
        $animal = $id ? $model->find($id) : null;

        if ($this->request->getMethod() === 'POST') {
            $data = $this->request->getPost(['name', 'species', 'breed', 'age', 'weight', 'color', 'location', 'gender', 'status', 'health', 'intake_date', 'behavior_notes', 'medical_notes']);
            foreach (['catch', 'neuter', 'vaccinate', 'return'] as $step) {
                $data['cnvr_' . $step] = $this->request->getPost('cnvr_' . $step) === '1' ? 1 : 0;
            }
            $data['species'] = trim((string) ($data['species'] ?? '')) ?: 'Cat';
            $data['image_url'] = $animal['image_url'] ?? null;
            $uploadPath = FCPATH . 'uploads/cats/';
            $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];

            $image = $this->request->getFile('image');
            if ($image && $image->getError() !== UPLOAD_ERR_NO_FILE) {
                if (! $image->isValid() || $image->hasMoved()) {
                    return redirect()->back()->withInput()->with('error', 'Please choose a valid cat image.');
                }

                if (! in_array($image->getMimeType(), $allowedTypes, true) || $image->getSizeByUnit('mb') > 5) {
                    return redirect()->back()->withInput()->with('error', 'Upload a JPG, PNG, or WebP image up to 5 MB.');
                }

                if (! is_dir($uploadPath) && ! mkdir($uploadPath, 0755, true) && ! is_dir($uploadPath)) {
                    return redirect()->back()->withInput()->with('error', 'Unable to prepare the image upload folder.');
                }

                $fileName = $image->getRandomName();
                $image->move($uploadPath, $fileName);
                $data['image_url'] = 'uploads/cats/' . $fileName;
            }

            if (! $id && empty($data['image_url'])) {
                return redirect()->back()->withInput()->with('error', 'Please upload a photo of the cat.');
            }
            $animalId = $id ?: (int) $model->insert($data, true);
            if ($id) $model->update($id, $data);

            $journey = new AnimalJourneyEventModel();
            if (! $id) {
                $journey->insert(['animal_id' => $animalId, 'event_type' => 'Intake', 'title' => 'Cat intake recorded', 'description' => 'Initial rescue and intake record created in CatCare 360.', 'event_date' => $data['intake_date'] ?: date('Y-m-d')]);
                if ($data['status'] === 'Available') {
                    $this->emailAdoptersAboutNewCat($animalId, (string) $data['name'], (string) $data['breed'], (string) $data['age']);
                }
            }
            $eventTitle = trim((string) $this->request->getPost('event_title'));
            if ($eventTitle !== '') {
                $journey->insert(['animal_id' => $animalId, 'event_type' => (string) $this->request->getPost('event_type') ?: 'Care Update', 'title' => $eventTitle, 'description' => trim((string) $this->request->getPost('event_description')), 'event_date' => (string) $this->request->getPost('event_date') ?: date('Y-m-d')]);
            }

            $galleryPhotos = $this->request->getFiles()['gallery_photos'] ?? [];
            $photoModel = new AnimalPhotoModel();
            foreach ($galleryPhotos as $galleryPhoto) {
                if (! $galleryPhoto || $galleryPhoto->getError() === UPLOAD_ERR_NO_FILE) continue;
                if (! $galleryPhoto->isValid() || $galleryPhoto->hasMoved() || ! in_array($galleryPhoto->getMimeType(), $allowedTypes, true) || $galleryPhoto->getSizeByUnit('mb') > 5) {
                    return redirect()->back()->withInput()->with('error', 'Every backup photo must be a JPG, PNG, or WebP image up to 5 MB.');
                }
                if (! is_dir($uploadPath) && ! mkdir($uploadPath, 0755, true) && ! is_dir($uploadPath)) return redirect()->back()->withInput()->with('error', 'Unable to prepare the image upload folder.');
                $galleryName = $galleryPhoto->getRandomName();
                $galleryPhoto->move($uploadPath, $galleryName);
                $photoModel->insert(['animal_id' => $animalId, 'photo_url' => 'uploads/cats/' . $galleryName]);
            }

            return redirect()->to('/admin/animals')->with('success', 'Cat record saved.');
        }

        return view('admin/animals/form', ['title' => $id ? 'Edit Cat' : 'Add Cat', 'animal' => $animal, 'galleryPhotos' => $id ? (new AnimalPhotoModel())->where('animal_id', $id)->findAll() : [], 'journeyEvents' => $id ? (new AnimalJourneyEventModel())->where('animal_id', $id)->orderBy('event_date', 'DESC')->findAll() : []]);
    }

    public function detail(int $id)
    {
        return $this->form($id);
    }

    public function delete(int $id)
    {
        (new AnimalModel())->delete($id);

        return redirect()->to('/admin/animals')->with('success', 'Cat record deleted.');
    }

    private function emailAdoptersAboutNewCat(int $animalId, string $catName, string $breed, string $age): void
    {
        $adopters = (new AdopterUserModel())->select('email, first_name')->where('status', 'active')->findAll();
        $catUrl = site_url('adopter/cats/' . $animalId);
        $config = config('Email');
        foreach ($adopters as $adopter) {
            if (empty($adopter['email'])) continue;
            $name = esc(trim((string) ($adopter['first_name'] ?? '')) ?: 'Cat lover');
            $email = service('email');
            $email->setFrom($config->fromEmail ?: 'no-reply@catcare360.org', $config->fromName ?: 'CatCare 360');
            $email->setTo($adopter['email']);
            $email->setSubject('Meet ' . $catName . ' — a new cat is available');
            $email->setMailType('html');
            $email->setMessage('<div style="font-family:Arial,sans-serif;max-width:560px;margin:auto;padding:28px;border:1px solid #e5e7eb;border-radius:16px"><h2 style="color:#2563eb">🐾 New cat available</h2><p>Hello ' . $name . ',</p><h1 style="font-size:24px">Meet ' . esc($catName) . '</h1><p>A new cat is now available for adoption at CatCare 360.</p><p><strong>Breed:</strong> ' . esc($breed ?: 'Not specified') . '<br><strong>Age:</strong> ' . esc($age ?: 'Not specified') . '</p><p><a href="' . esc($catUrl) . '" style="display:inline-block;padding:12px 16px;background:#2563eb;color:white;text-decoration:none;border-radius:8px">View ' . esc($catName) . '</a></p></div>');
            if (! $email->send()) {
                log_message('error', 'New-cat email could not be sent to {email}.', ['email' => $adopter['email']]);
            }
        }
    }
}
