<?php

declare(strict_types=1);

/**
 * Small helper functions shared across the app.
 */

/** Read an environment variable with an optional default. */
function env(string $key, ?string $default = null): ?string
{
    $value = $_ENV[$key] ?? getenv($key);
    if ($value === false || $value === null || $value === '') {
        return $default;
    }
    return $value;
}

/** Escape a string for safe HTML output. */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Send an HTTP redirect and stop. */
function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

/** Store a one-time flash message. */
function flash(string $message, string $type = 'info'): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

/** Pull and clear all flash messages. */
function take_flashes(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

/** Render a view file with the given data and return the HTML. */
function view(string $name, array $data = []): string
{
    $file = dirname(__DIR__) . '/views/' . $name . '.php';
    if (!is_file($file)) {
        throw new RuntimeException("View not found: {$name}");
    }
    extract($data, EXTR_SKIP);
    ob_start();
    require $file;
    return (string) ob_get_clean();
}

/** Human-readable label + CSS class for a workflow status. */
function status_badge(string $status): array
{
    return match ($status) {
        'draft'     => ['Draft', 'badge-gray'],
        'submitted' => ['Submitted', 'badge-blue'],
        'verified'  => ['Verified', 'badge-amber'],
        'approved'  => ['Approved', 'badge-green'],
        'rejected'  => ['Rejected', 'badge-red'],
        default     => [ucfirst($status), 'badge-gray'],
    };
}
