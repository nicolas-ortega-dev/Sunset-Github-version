<?php
declare(strict_types=1);

namespace Sunset\Controllers;

use PDOException;
use Sunset\Core\View;
use Sunset\Helpers\Auth;
use Sunset\Helpers\Csrf;
use Sunset\Helpers\Flash;
use Sunset\Helpers\Validator;
use Sunset\Models\User;

final class AuthController
{
    /** GET ?page=auth&mode=register|login */
    public function showForm(): void
    {
        Auth::guestOnly();
        $mode = ($_GET['mode'] ?? 'register') === 'login' ? 'login' : 'register';
        $this->renderForm($mode);
    }

    /** POST ?page=register */
    public function register(): void
    {
        Auth::guestOnly();
        Csrf::verify();

        $username = input('username');
        $email    = mb_strtolower(input('email'));
        $password = (string) ($_POST['password'] ?? '');

        $errors = array_filter([
            'username' => Validator::username($username),
            'email'    => Validator::email($email),
            'password' => Validator::password($password),
        ]);

        if (!isset($errors['username']) && User::usernameExists($username)) {
            $errors['username'] = 'Ese nombre de usuario ya está en uso.';
        }
        if (!isset($errors['email']) && User::emailExists($email)) {
            $errors['email'] = 'Ya existe una cuenta con ese correo.';
        }
        if ($errors) {
            $this->renderForm('register', $errors, ['username' => $username, 'email' => $email]);
            return;
        }

        try {
            $id = User::create($username, $email, $password);
        } catch (PDOException $ex) {
            if ($ex->getCode() === '23000') { // duplicado por carrera entre dos registros
                $this->renderForm('register', ['email' => 'Ese usuario o correo ya existe.'],
                    ['username' => $username, 'email' => $email]);
                return;
            }
            throw $ex;
        }

        Auth::login($id);
        Flash::add('success', "Cuenta creada. ¡Bienvenido a Sunset, $username!");
        redirect('home');
    }

    /** POST ?page=login */
    public function login(): void
    {
        Auth::guestOnly();
        Csrf::verify();

        $login    = input('login');
        $password = (string) ($_POST['password'] ?? '');

        $user = $login !== '' ? User::findByLogin($login) : null;
        // Se verifica siempre un hash para que el tiempo de respuesta no delate si el usuario existe.
        $hash = $user['password_hash'] ?? password_hash('sunset-dummy', PASSWORD_DEFAULT);
        $ok   = password_verify($password, $hash) && $user !== null;

        if (!$ok) {
            $this->renderForm('login', ['general' => 'Usuario o contraseña incorrectos.'], ['login' => $login]);
            return;
        }

        Auth::login((int) $user['id']);
        redirect('home');
    }

    /** POST ?page=logout */
    public function logout(): void
    {
        Csrf::verify();
        Auth::logout();
        redirect('auth', ['mode' => 'login']);
    }

    private function renderForm(string $mode, array $errors = [], array $old = []): void
    {
        if ($errors) {
            http_response_code(422);
        }
        View::render('auth/form', [
            'title'  => $mode === 'login' ? 'Iniciar sesión' : 'Únete a Sunset',
            'mode'   => $mode,
            'errors' => $errors,
            'old'    => $old,
        ], 'guest');
    }
}
