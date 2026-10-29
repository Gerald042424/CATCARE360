<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;

class VolunteerAuthFilter extends AuthSessionTimeoutFilter
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (! session('volunteer_user')) {
            return redirect()->to('/')->with('error', 'Please sign in to continue.')->with('auth_modal', 'signin');
        }

        return $this->enforceTimeout(
            $request,
            'volunteer',
            (string) session('volunteer_user.id'),
            config('AuthSession')->volunteerIdleSeconds
        );
    }
}
