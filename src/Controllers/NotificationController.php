<?php declare(strict_types=1);
namespace Sunset\Controllers;

use Sunset\Core\View;
use Sunset\Helpers\Auth;
use Sunset\Helpers\Csrf;
use Sunset\Models\Follow;
use Sunset\Models\Notification;

final class NotificationController {

    /** Página completa (fallback, por si se accede directamente) */
    public function index(): void {
        Auth::requireLogin();
        $notifs = Notification::allForUser((int)Auth::id());
        Notification::markAllRead((int)Auth::id());
        View::render('notification/index', ['title' => 'Notificaciones', 'notifs' => $notifs]);
    }

    /** AJAX: HTML del panel + marcar como leídas */
    public function panel(): void {
        if (!Auth::check()) { json_response(['error' => 'No autorizado'], 401); }
        $notifs = Notification::allForUser((int)Auth::id());
        Notification::markAllRead((int)Auth::id());
        $html = View::partial('partials/notif_panel_body', ['notifs' => $notifs]);
        json_response(['html' => $html, 'count' => count($notifs)]);
    }

    public function markRead(): void {
        Auth::requireLogin();
        Csrf::verify();
        Notification::markAllRead((int)Auth::id());
        redirect('notifications');
    }
}
