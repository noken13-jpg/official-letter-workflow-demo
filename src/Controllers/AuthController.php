<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Csrf;

final class AuthController
{
    public static function showLogin(): string
    {
        if (Auth::check()) {
            redirect('/');
        }
        return view('login', ['title' => 'Sign in']);
    }

    public static function login(): void
    {
        Csrf::check($_POST['csrf_token'] ?? null);
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if (Auth::attempt($username, $password)) {
            flash('Welcome back, ' . Auth::user()['name'] . '.', 'success');
            redirect('/');
        }
        flash('Invalid username or password.', 'error');
        redirect('/login');
    }

    public static function logout(): void
    {
        Csrf::check($_POST['csrf_token'] ?? null);
        Auth::logout();
        redirect('/login');
    }
}
