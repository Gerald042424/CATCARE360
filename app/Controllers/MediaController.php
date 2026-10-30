<?php

namespace App\Controllers;

use App\Services\CloudStorageService;
use Throwable;

class MediaController extends BaseController
{
    public function show(string $reference)
    {
        try {
            $storage = new CloudStorageService();
            $pathname = $storage->pathname($reference);
            $isPublic = str_starts_with($pathname, 'cats/')
                || str_starts_with($pathname, 'volunteers/');

            if (str_starts_with($pathname, 'applications/')
                && ! $this->canAccessApplicationDocument($reference)) {
                return $this->response->setStatusCode(404);
            }

            if (! $isPublic && ! str_starts_with($pathname, 'applications/') && ! $this->hasAuthenticatedUser()) {
                return $this->response->setStatusCode(404);
            }

            $media = $storage->fetch($reference);

            return $this->response
                ->setHeader('Content-Type', $media['contentType'])
                ->setHeader('X-Content-Type-Options', 'nosniff')
                ->setHeader('Cache-Control', $isPublic ? 'public, max-age=86400' : 'private, no-store')
                ->setBody($media['body']);
        } catch (Throwable $exception) {
            log_message('error', 'Cloud media delivery failed: {message}', ['message' => $exception->getMessage()]);
            return $this->response->setStatusCode(404);
        }
    }

    private function hasAuthenticatedUser(): bool
    {
        return (int) session('staff_id') > 0
            || is_array(session('adopter_user'))
            || is_array(session('volunteer_user'));
    }

    private function canAccessApplicationDocument(string $reference): bool
    {
        if ((int) session('staff_id') > 0) {
            return true;
        }

        $adopterId = (int) session('adopter_user.adopter_id');
        if ($adopterId <= 0) {
            return false;
        }

        $storedReference = 'media/' . $reference;
        $builder = db_connect()->table('adoption_applications')
            ->where('adopter_id', $adopterId)
            ->groupStart()
            ->where('selfie_path', $storedReference)
            ->orWhere('gov_id_path', $storedReference)
            ->orWhere('address_proof_path', $storedReference)
            ->orWhere('home_photos_path', $storedReference)
            ->groupEnd();

        if ($builder->countAllResults() > 0) {
            return true;
        }

        // The second home-area image is stored in server-owned verification
        // metadata so existing production databases do not require a schema
        // change. It receives the same owner-only authorization as the legacy
        // application document columns above.
        $applications = db_connect()->table('adoption_applications')
            ->select('verification_metadata')
            ->where('adopter_id', $adopterId)
            ->get()
            ->getResultArray();

        foreach ($applications as $application) {
            $metadata = json_decode((string) ($application['verification_metadata'] ?? ''), true);
            if (is_array($metadata)
                && ($metadata['home_access']['stored_path'] ?? null) === $storedReference) {
                return true;
            }
        }

        return false;
    }
}
