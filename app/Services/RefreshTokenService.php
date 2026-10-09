<?php

namespace App\Services;

use App\Models\RefreshToken;
use App\Models\User;
use App\Support\ClientInfo;
use App\Support\HttpException;
use Illuminate\Database\Capsule\Manager as DB;

class RefreshTokenService
{
    private const TTL_DAYS = 30;

    public function issue(User $user): string
    {
        $plain = bin2hex(random_bytes(32));
        $hash = hash('sha256', $plain);

        $this->enforceDeviceLimit($user);

        RefreshToken::create([
            'user_id'     => $user->id,
            'token_hash'  => $hash,
            'expires_at'  => date('Y-m-d H:i:s', strtotime('+' . self::TTL_DAYS . ' days')),
            'user_agent'  => ClientInfo::userAgent(),
            'ip'          => ClientInfo::ip(),
        ]);

        return $plain;
    }

    private function enforceDeviceLimit(User $user, int $max = 5): void
    {
        $active = RefreshToken::where('user_id', $user->id)
            ->whereNull('revoked_at')
            ->where('expires_at', '>', date('Y-m-d H:i:s', time()))
            ->orderByDesc('created_at')
            ->get();

        if ($active->count() >= $max) {
            $toRevoke = $active->slice($max - 1);
            foreach ($toRevoke as $token) {
                $token->update(['revoked_at' => date('Y-m-d H:i:s', time())]);
            }
        }
    }

    public function verify(string $plain): RefreshToken
    {
        $hash = hash('sha256', $plain);

        $token = RefreshToken::where('token_hash', $hash)->first();

        if (!$token) {
            /*import_log('refresh token verification failed', [
                'message' => 'Invalid refresh token',
                'client_info' => $this->client
            ], $this->logfile);*/
            throw HttpException::unauthorized('Invalid refresh token');
        }

        if ($token->revoked_at !== null) {
            throw HttpException::unauthorized('Refresh token revoked');
        }

        if ($token->expires_at < date('Y-m-d H:i:s')) {
            throw HttpException::unauthorized('Refresh token expired');
        }

        return $token;
    }

    public function rotate(RefreshToken $old): string
    {
        return DB::connection()->transaction(function () use ($old) {
            $plain = bin2hex(random_bytes(32));
            $hash = hash('sha256', $plain);

            $new = RefreshToken::create([
                'user_id'     => $old->user_id,
                'device_id'   => $old->device_id,
                'device_name' => $old->device_name,
                'token_hash'  => $hash,
                'expires_at'  => date('Y-m-d H:i:s', strtotime('+' . self::TTL_DAYS . ' days')),
                'user_agent'  => ClientInfo::userAgent(),
                'ip'          => ClientInfo::ip(),
            ]);

            $old->update([
                'revoked_at'  => date('Y-m-d H:i:s'),
                'replaced_by' => $new->id,
            ]);

            return $plain;
        });
    }

    public function revoke(string $plain): void
    {
        $hash = hash('sha256', $plain);
        RefreshToken::where('token_hash', $hash)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => date('Y-m-d H:i:s')]);
    }

    public function revokeAllForUser(User $user): void
    {
        RefreshToken::where('user_id', $user->id)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => date('Y-m-d H:i:s')]);
    }
}