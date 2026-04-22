<?php

namespace App\Support;

class AdminRole
{
    private const ELEVATED = ['admin', 'superadmin'];

    public static function isElevated(mixed $user): bool
    {
        if (!is_array($user)) {
            return false;
        }

        $rol = (string) ($user['rol'] ?? '');
        if ($rol === '' && is_array($user['roles'] ?? null) && $user['roles'] !== []) {
            $first = $user['roles'][0] ?? null;
            if (is_string($first)) {
                $rol = $first;
            } elseif (is_array($first) && isset($first['name'])) {
                $rol = (string) $first['name'];
            }
        }

        return in_array(strtolower($rol), self::ELEVATED, true);
    }
}
