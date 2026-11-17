<?php

namespace App\Services;

use App\Models\TillSession;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class TillSessionService
{
    public function open(User $cashier, string $openingBalance): TillSession
    {
        if (! $cashier->is_active || $cashier->branch_id === null) {
            throw new DomainException('Only active branch staff can open a till.');
        }

        if ((float) $openingBalance < 0) {
            throw new DomainException('Opening balance cannot be negative.');
        }

        return DB::transaction(function () use ($cashier, $openingBalance): TillSession {
            User::query()->lockForUpdate()->findOrFail($cashier->id);

            if ($cashier->tillSessions()->where('status', 'Open')->exists()) {
                throw new DomainException('The cashier already has an open till.');
            }

            return $cashier->tillSessions()->create([
                'branch_id' => $cashier->branch_id,
                'opening_balance' => $openingBalance,
                'expected_balance' => $openingBalance,
                'status' => 'Open',
                'opened_at' => now(),
            ]);
        });
    }

    public function close(TillSession $session, string $actualBalance, ?string $differenceReason = null): TillSession
    {
        return DB::transaction(function () use ($session, $actualBalance, $differenceReason): TillSession {
            $lockedSession = TillSession::query()->lockForUpdate()->findOrFail($session->id);

            if ($lockedSession->status !== 'Open') {
                throw new DomainException('Only an open till session may be closed.');
            }

            $difference = $this->toMinorUnits($actualBalance)
                - $this->toMinorUnits($lockedSession->expected_balance);

            if ($difference !== 0 && blank($differenceReason)) {
                throw new DomainException('A till difference requires an explanation.');
            }

            $lockedSession->update([
                'actual_balance' => $actualBalance,
                'difference_amount' => $this->fromMinorUnits($difference),
                'difference_reason' => $differenceReason,
                'status' => $difference === 0 ? 'Closed' : 'Pending Difference Approval',
                'closed_at' => now(),
            ]);

            return $lockedSession->refresh();
        });
    }

    public function authorizeDifference(TillSession $session, User $authorizer): TillSession
    {
        return DB::transaction(function () use ($session, $authorizer): TillSession {
            $session = TillSession::query()->lockForUpdate()->findOrFail($session->id);

            if ($session->status !== 'Pending Difference Approval' || $session->user_id === $authorizer->id) {
                throw new DomainException('A pending till difference must be authorized by another employee.');
            }

            $session->update(['status' => 'Closed', 'difference_authorized_by' => $authorizer->id, 'difference_authorized_at' => now()]);

            return $session->refresh();
        });
    }

    private function toMinorUnits(string|int|float|null $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }

    private function fromMinorUnits(int $amount): string
    {
        return number_format($amount / 100, 2, '.', '');
    }
}
