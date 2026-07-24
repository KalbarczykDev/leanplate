<?php

// Feedback persistence, notifications, and shared feedback widget.
declare(strict_types=1);

function save_feedback(
    string $message,
    ?string $email,
    ?array $user
): void {
    db()->prepare(
        'INSERT INTO feedback (user_id, email, message) VALUES (?, ?, ?)'
    )->execute([
        $user['id'] ?? null,
        $email,
        $message,
    ]);

    $admin = (string)(config()['admin_email'] ?? '');

    if ($admin !== '') {
        @send_mail(
            $admin,
            'New feedback',
            $message . "\n\nfrom: " . ($email ?? 'anonymous')
        );
    }
}

function feedback_widget(): void
{
    $csrf = csrf_field();

    echo <<<HTML
    <button class="fb-fab" type="button" onclick="document.getElementById('fb-modal').showModal()">Feedback</button>
    <dialog id="fb-modal" class="modal">
        <h2>Feedback</h2>
        <form method="post" action="/feedback">
            $csrf
            <label for="fb-message">What's on your mind?</label>
            <textarea id="fb-message" name="message" required></textarea>
            <label for="fb-email">Email (optional)</label>
            <input id="fb-email" type="email" name="email" placeholder="you@example.com">
            <div class="modal-actions" style="display:flex">
                <button class="btn" type="submit">Send</button>
                <button class="btn btn-secondary" type="button" onclick="this.closest('dialog').close()">Cancel</button>
            </div>
        </form>
    </dialog>

HTML;
}
