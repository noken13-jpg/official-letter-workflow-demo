<?php

declare(strict_types=1);

namespace App;

use InvalidArgumentException;
use PDO;
use RuntimeException;

/**
 * Letter workflow model: creation, numbering, status transitions and
 * the audit trail. Every state change is written to letter_events.
 */
final class Letters
{
    /** Allowed transitions: action => [from, to, permission] */
    private const TRANSITIONS = [
        'submit'  => ['draft',     'submitted', 'letter.submit'],
        'verify'  => ['submitted', 'verified',  'letter.verify'],
        'approve' => ['verified',  'approved',  'letter.approve'],
    ];

    public static function all(): array
    {
        return Database::connection()->query(
            'SELECT l.*, u.name AS author
               FROM letters l JOIN users u ON u.id = l.created_by
              ORDER BY l.created_at DESC'
        )->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT l.*, u.name AS author
               FROM letters l JOIN users u ON u.id = l.created_by
              WHERE l.id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function events(int $letterId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT ev.*, u.name AS actor
               FROM letter_events ev JOIN users u ON u.id = ev.user_id
              WHERE ev.letter_id = :id ORDER BY ev.id ASC'
        );
        $stmt->execute([':id' => $letterId]);
        return $stmt->fetchAll();
    }

    /** Create a new draft letter and log the event. Returns the new id. */
    public static function create(array $input, int $userId): int
    {
        $subject   = trim($input['subject'] ?? '');
        $recipient = trim($input['recipient'] ?? '');
        $body      = trim($input['body'] ?? '');

        if ($subject === '' || $recipient === '' || $body === '') {
            throw new InvalidArgumentException('Subject, recipient and body are required.');
        }
        if (mb_strlen($subject) > 200 || mb_strlen($recipient) > 160) {
            throw new InvalidArgumentException('Subject or recipient is too long.');
        }

        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO letters (year, subject, recipient, body, status, created_by)
                 VALUES (:y, :s, :r, :b, "draft", :u)'
            );
            $stmt->execute([
                ':y' => (int) date('Y'),
                ':s' => $subject,
                ':r' => $recipient,
                ':b' => $body,
                ':u' => $userId,
            ]);
            $id = (int) $pdo->lastInsertId();
            self::logEvent($pdo, $id, $userId, 'create', null, 'draft', 'Created');
            $pdo->commit();
            return $id;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Apply a workflow transition (submit/verify/approve). Assigns an
     * official number at approval time. Returns the updated letter.
     */
    public static function transition(int $id, string $action, int $userId, string $note = ''): array
    {
        if (!isset(self::TRANSITIONS[$action])) {
            throw new InvalidArgumentException('Unknown action.');
        }
        [$from, $to] = self::TRANSITIONS[$action];

        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            // Lock the row so concurrent requests cannot double-apply.
            $stmt = $pdo->prepare('SELECT * FROM letters WHERE id = :id FOR UPDATE');
            $stmt->execute([':id' => $id]);
            $letter = $stmt->fetch();
            if (!$letter) {
                throw new RuntimeException('Letter not found.');
            }
            if ($letter['status'] !== $from) {
                throw new RuntimeException("Cannot {$action}: letter is '{$letter['status']}', expected '{$from}'.");
            }

            $number = $letter['number'];
            if ($action === 'approve') {
                $number = self::allocateNumber($pdo, (int) $letter['year']);
                $pdo->prepare('UPDATE letters SET number = :n, status = :st WHERE id = :id')
                    ->execute([':n' => $number, ':st' => $to, ':id' => $id]);
            } else {
                $pdo->prepare('UPDATE letters SET status = :st WHERE id = :id')
                    ->execute([':st' => $to, ':id' => $id]);
            }

            self::logEvent($pdo, $id, $userId, $action, $from, $to, $note !== '' ? $note : null);
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        return self::find($id);
    }

    /** Send a letter back to 'rejected' from submitted/verified. */
    public static function reject(int $id, int $userId, string $note = ''): array
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('SELECT * FROM letters WHERE id = :id FOR UPDATE');
            $stmt->execute([':id' => $id]);
            $letter = $stmt->fetch();
            if (!$letter) {
                throw new RuntimeException('Letter not found.');
            }
            if (!in_array($letter['status'], ['submitted', 'verified'], true)) {
                throw new RuntimeException('Only submitted or verified letters can be rejected.');
            }
            $pdo->prepare('UPDATE letters SET status = "rejected" WHERE id = :id')
                ->execute([':id' => $id]);
            self::logEvent($pdo, $id, $userId, 'reject', $letter['status'], 'rejected', $note !== '' ? $note : null);
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
        return self::find($id);
    }

    public static function permissionFor(string $action): string
    {
        return self::TRANSITIONS[$action][2] ?? 'letter.view';
    }

    /** Atomically allocate the next per-year running number. */
    private static function allocateNumber(PDO $pdo, int $year): string
    {
        $pdo->prepare(
            'INSERT INTO letter_counters (year, last_seq) VALUES (:y, 1)
             ON DUPLICATE KEY UPDATE last_seq = last_seq + 1'
        )->execute([':y' => $year]);

        $stmt = $pdo->prepare('SELECT last_seq FROM letter_counters WHERE year = :y FOR UPDATE');
        $stmt->execute([':y' => $year]);
        $seq = (int) $stmt->fetchColumn();

        $code = env('LETTER_CODE', 'OLW');
        return sprintf('%03d/%s/%d', $seq, $code, $year);
    }

    private static function logEvent(
        PDO $pdo,
        int $letterId,
        int $userId,
        string $action,
        ?string $from,
        ?string $to,
        ?string $note
    ): void {
        $pdo->prepare(
            'INSERT INTO letter_events (letter_id, user_id, action, from_status, to_status, note)
             VALUES (:l, :u, :a, :f, :t, :n)'
        )->execute([
            ':l' => $letterId,
            ':u' => $userId,
            ':a' => $action,
            ':f' => $from,
            ':t' => $to,
            ':n' => $note !== null ? mb_substr($note, 0, 500) : null,
        ]);
    }
}
