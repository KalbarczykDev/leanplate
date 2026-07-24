<?php

// Account profile updates and account deletion.
declare(strict_types=1);

function update_account(int $userId, string $displayName): void
{
    db()->prepare(
        'UPDATE users SET display_name = ? WHERE id = ?'
    )->execute([$displayName, $userId]);
}

function delete_account(array $user): void
{
    stripe_cancel_subscription($user);

    db()->prepare(
        'DELETE FROM feedback WHERE user_id = ?'
    )->execute([$user['id']]);

    db()->prepare(
        'DELETE FROM users WHERE id = ?'
    )->execute([$user['id']]);

    logout_user();
}
