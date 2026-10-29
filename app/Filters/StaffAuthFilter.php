<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;

class StaffAuthFilter extends AuthSessionTimeoutFilter
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (! session()->get('staff_id')) {
            return redirect()->to('/')->with('error', 'Please sign in to continue.')->with('auth_modal', 'signin');
        }

        return $this->enforceTimeout(
            $request,
            'staff',
            (string) session()->get('staff_id'),
            config('AuthSession')->staffIdleSeconds
        );
    }
}
