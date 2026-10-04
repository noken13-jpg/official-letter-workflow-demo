<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Csrf;
use App\Letters;
use App\Pdf;
use InvalidArgumentException;
use Throwable;

final class LetterController
{
    public static function dashboard(): string
    {
        Auth::requireLogin();
        $letters = Letters::all();
        $counts = ['draft' => 0, 'submitted' => 0, 'verified' => 0, 'approved' => 0, 'rejected' => 0];
        foreach ($letters as $l) {
            $counts[$l['status']] = ($counts[$l['status']] ?? 0) + 1;
        }
        return view('dashboard', [
            'title'   => 'Dashboard',
            'letters' => $letters,
            'counts'  => $counts,
        ]);
    }

    public static function index(): string
    {
        Auth::requirePermission('letter.view');
        return view('letters/index', [
            'title'   => 'Letters',
            'letters' => Letters::all(),
        ]);
    }

    public static function create(): string
    {
        Auth::requirePermission('letter.create');
        return view('letters/create', ['title' => 'New letter']);
    }

    public static function store(): void
    {
        Auth::requirePermission('letter.create');
        Csrf::check($_POST['csrf_token'] ?? null);
        try {
            $id = Letters::create($_POST, Auth::user()['id']);
            flash('Draft letter created.', 'success');
            redirect('/letters/show?id=' . $id);
        } catch (InvalidArgumentException $e) {
            flash($e->getMessage(), 'error');
            redirect('/letters/create');
        }
    }

    public static function show(): string
    {
        Auth::requirePermission('letter.view');
        $id = (int) ($_GET['id'] ?? 0);
        $letter = Letters::find($id);
        if (!$letter) {
            http_response_code(404);
            return view('error', ['title' => 'Not found', 'message' => 'Letter not found.']);
        }
        return view('letters/show', [
            'title'  => 'Letter ' . ($letter['number'] ?? '(draft)'),
            'letter' => $letter,
            'events' => Letters::events($id),
        ]);
    }

    /** Handles submit / verify / approve / reject via POST. */
    public static function action(string $action): void
    {
        Csrf::check($_POST['csrf_token'] ?? null);
        $id = (int) ($_POST['id'] ?? 0);
        $note = trim((string) ($_POST['note'] ?? ''));

        $permission = $action === 'reject' ? 'letter.reject' : Letters::permissionFor($action);
        Auth::requirePermission($permission);

        try {
            if ($action === 'reject') {
                Letters::reject($id, Auth::user()['id'], $note);
                flash('Letter rejected.', 'success');
            } else {
                $letter = Letters::transition($id, $action, Auth::user()['id'], $note);
                $msg = $action === 'approve'
                    ? 'Letter approved and numbered ' . $letter['number'] . '.'
                    : ucfirst($action) . ' done.';
                flash($msg, 'success');
            }
        } catch (Throwable $e) {
            flash($e->getMessage(), 'error');
        }
        redirect('/letters/show?id=' . $id);
    }

    public static function pdf(): void
    {
        Auth::requirePermission('letter.pdf');
        $id = (int) ($_GET['id'] ?? 0);
        $letter = Letters::find($id);
        if (!$letter) {
            http_response_code(404);
            echo 'Letter not found.';
            return;
        }
        $output = Pdf::letter($letter);
        $name = 'letter-' . ($letter['number'] ? str_replace('/', '_', $letter['number']) : 'draft-' . $id) . '.pdf';
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $name . '"');
        header('Content-Length: ' . strlen($output));
        echo $output;
    }
}
