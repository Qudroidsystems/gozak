<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Auth\AuthenticatesUsers;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/dashboard';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }

    /**
     * The user has been logged in.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $user
     * @return mixed
     */
    protected function authenticated(Request $request, $user)
    {
        // Check if the user has 'staff' in the role column
        if ($user->role !== 'staff') {
            // Log out the user immediately
            auth()->logout();

            // Throw validation exception to mimic login failure
            throw ValidationException::withMessages([
                $this->username() => [trans('auth.failed_staff_role')],
            ]);
        }

        // Riders (no back-office access) go straight to the rider app.
        if ($user->can('Deliver orders') && !$user->can('View order') && !$user->can('View dispatch')) {
            return redirect()->route('rider.index');
        }

        // If role check passes, proceed to default redirect
        return redirect()->intended($this->redirectPath());
    }
}