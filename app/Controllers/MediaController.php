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

            if (! $isPublic && ! $this->hasAuthenticatedUser()) {
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
}
