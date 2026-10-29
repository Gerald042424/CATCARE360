<?php

namespace App\Filters;

use App\Services\AuthSessionPolicy;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

abstract class AuthSessionTimeoutFilter implements FilterInterface
{
    protected function enforceTimeout(
        RequestInterface $request,
        string $role,
        string $identity,
        int $idleSeconds
    ): ?ResponseInterface {
        $session = session();
        $now = time();
        $sessionIdentity = $role . ':' . $identity;
        $storedIdentity = (string) $session->get('auth_session_identity');

        if ($storedIdentity !== $sessionIdentity) {
            $session->set([
                'auth_session_identity' => $sessionIdentity,
                'auth_session_started_at' => $now,
                'auth_last_activity_at' => $now,
            ]);

            return null;
        }

        $startedAt = (int) $session->get('auth_session_started_at');
        $lastActivityAt = (int) $session->get('auth_last_activity_at');
        $absoluteSeconds = config('AuthSession')->absoluteSeconds;
        $expirationReason = (new AuthSessionPolicy())->expirationReason(
            $now,
            $startedAt,
            $lastActivityAt,
            $idleSeconds,
            $absoluteSeconds
        );

        if ($expirationReason !== null) {
            $message = $expirationReason === 'absolute'
                ? 'Your session reached its security time limit. Please sign in again.'
                : 'Your session expired due to inactivity. Please sign in again.';

            $session->destroy();

            if ($request->isAJAX() || str_contains(strtolower($request->getHeaderLine('Accept')), 'application/json')) {
                return service('response')
                    ->setStatusCode(401)
                    ->setJSON(['expired' => true, 'message' => $message]);
            }

            return redirect()->to('/')
                ->with('error', $message)
                ->with('auth_modal', 'signin')
                ->with('signin_type', $role === 'staff' ? 'staff' : $role);
        }

        $session->set('auth_last_activity_at', $now);

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
