<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;

class AdopterAuthFilter extends AuthSessionTimeoutFilter
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (! session('adopter_user')) {
            return redirect()->to('/')->with('error', 'Please sign in to continue.')->with('auth_modal', 'signin')->with('signin_type', 'adopter');
        }

        return $this->enforceTimeout(
            $request,
            'adopter',
            (string) session('adopter_user.id'),
            config('AuthSession')->adopterIdleSeconds
        );
    }
}
