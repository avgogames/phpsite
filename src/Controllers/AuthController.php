<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Repositories\UserRepository;
use App\Services\Auth;

final class AuthController extends Controller
{
    public function showLogin(Request $request): Response
    {
        return $this->view('auth/login', ['title' => 'Sign in']);
    }

    public function login(Request $request): Response
    {
        $input = $request->only(['email', 'password']);
        $errors = Validator::validate($input, ['email' => 'required|email', 'password' => 'required']);
        if ($errors) {
            return $this->backWithErrors('/login', $input, $errors);
        }

        if (Auth::tooManyAttempts($input['email'], $request->ip)) {
            Session::flash('error', 'Too many sign-in attempts. Please wait ' . Auth::LOCKOUT_MINUTES . ' minutes and try again.');
            return $this->redirect('/login');
        }

        $user = Auth::attempt($input['email'], $input['password']);
        if ($user === null) {
            Auth::recordFailure($input['email'], $request->ip);
            Session::flashInput($input, []);
            Session::flash('error', 'Email or password is incorrect.');
            return $this->redirect('/login');
        }

        Auth::clearFailures($input['email']);
        $intended = $this->pullIntended();
        Auth::login($user); // regenerates the session id; the guest cart is kept
        Session::flash('success', 'Welcome back, ' . $user['name'] . '.');

        return $this->redirect($user['role'] === 'admin' && $intended === '/' ? '/admin' : $intended);
    }

    public function showRegister(Request $request): Response
    {
        return $this->view('auth/register', ['title' => 'Create an account']);
    }

    public function register(Request $request): Response
    {
        $input = $request->only(['name', 'email', 'password', 'password_confirmation']);
        $errors = Validator::validate($input, [
            'name' => 'required|max:120',
            'email' => 'required|email|max:255',
            'password' => 'required|min:10|max:200',
            'password_confirmation' => 'required|same:password',
        ]);

        $users = new UserRepository();
        if (!isset($errors['email']) && $users->findByEmail($input['email']) !== null) {
            $errors['email'] = 'An account with this email already exists.';
        }
        if ($errors) {
            return $this->backWithErrors('/register', $input, $errors);
        }

        $id = $users->create($input['email'], $input['name'], password_hash($input['password'], PASSWORD_DEFAULT));
        Auth::login($users->find($id));
        Session::flash('success', 'Your account is ready.');

        return $this->redirect($this->pullIntended());
    }

    public function logout(Request $request): Response
    {
        Auth::logout();
        return $this->redirect('/');
    }

    /** The page a guest was sent to login from; only same-site paths are honoured. */
    private function pullIntended(): string
    {
        $intended = (string) Session::get('intended', '/');
        Session::forget('intended');
        return str_starts_with($intended, '/') && !str_starts_with($intended, '//') ? $intended : '/';
    }
}
