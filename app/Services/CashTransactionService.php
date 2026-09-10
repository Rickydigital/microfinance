<?php

namespace App\Services;

use App\Models\CashTransaction;
use App\Models\NumberSequence;
use App\Models\TillSession;
use App\Models\User;
use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CashTransactionService
{
    public function post(User $user, string $type, string $direction, string $amount, Model $source, bool $requiresTill, ?int $branchId = null): CashTransaction
    {
        return DB::transaction(function () use ($user, $type, $direction, $amount, $source, $requiresTill, $branchId): CashTransaction {
            $session = $requiresTill
                ? TillSession::query()->where('user_id', $user->id)->where('status', 'Open')->lockForUpdate()->first()
                : null;

            if ($requiresTill && $session === null) {
                throw new DomainException('An open till is required for this cash transaction.');
            }

            if ($session !== null && $direction === 'Out' && (float) $amount > (float) $session->expected_balance) {
                throw new DomainException('The till does not have enough expected cash for this transaction.');
            }

            if ($session !== null) {
                $change = $direction === 'In' ? (float) $amount : -((float) $amount);
                $session->update(['expected_balance' => number_format((float) $session->expected_balance + $change, 2, '.', '')]);
            }

            return CashTransaction::query()->create([
                'branch_id' => $branchId ?? $user->branch_id,
                'till_session_id' => $session?->id,
                'transaction_number' => NumberSequence::issue('transaction'),
                'type' => $type,
                'direction' => $direction,
                'amount' => $amount,
                'source_type' => $source->getMorphClass(),
                'source_id' => $source->getKey(),
                'status' => 'Posted',
                'created_by' => $user->id,
                'posted_at' => now(),
            ]);
        });
    }

    public function reverse(CashTransaction $transaction, User $user, string $reason): CashTransaction
    {
        return DB::transaction(function () use ($transaction, $user, $reason): CashTransaction {
            $transaction = CashTransaction::query()->lockForUpdate()->findOrFail($transaction->id);

            if ($transaction->status !== 'Posted') {
                throw new DomainException('Only a posted cash transaction may be reversed.');
            }

            $session = $transaction->till_session_id ? TillSession::query()->lockForUpdate()->find($transaction->till_session_id) : null;

            if ($session !== null && $session->status !== 'Open') {
                throw new DomainException('A transaction from a closed till requires a controlled till adjustment.');
            }

            if ($session !== null) {
                $change = $transaction->direction === 'In' ? -((float) $transaction->amount) : (float) $transaction->amount;
                $session->update(['expected_balance' => number_format((float) $session->expected_balance + $change, 2, '.', '')]);
            }

            $reversal = CashTransaction::query()->create([
                'branch_id' => $transaction->branch_id,
                'till_session_id' => $transaction->till_session_id,
                'transaction_number' => NumberSequence::issue('transaction'),
                'type' => $transaction->type.' Reversal',
                'direction' => $transaction->direction === 'In' ? 'Out' : 'In',
                'amount' => $transaction->amount,
                'source_type' => $transaction->source_type,
                'source_id' => $transaction->source_id,
                'status' => 'Posted',
                'created_by' => $user->id,
                'reversed_transaction_id' => $transaction->id,
                'reversal_reason' => $reason,
                'posted_at' => now(),
            ]);
            $transaction->update(['status' => 'Reversed']);

            return $reversal;
        });
    }
}
