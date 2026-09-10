<?php

namespace App\Models;

use Database\Factories\LoanInstallmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use InvalidArgumentException;

class LoanInstallment extends Model
{
    /** @use HasFactory<LoanInstallmentFactory> */
    use HasFactory;

    protected $fillable = [
        'loan_id', 'installment_number', 'due_date', 'principal_due', 'interest_due',
        'fees_due', 'penalties_due', 'principal_paid', 'interest_paid', 'fees_paid',
        'penalties_paid', 'status', 'reminder_last_sent_at',
    ];

    protected function casts(): array
    {
        return ['due_date' => 'date', 'principal_due' => 'decimal:2', 'interest_due' => 'decimal:2', 'fees_due' => 'decimal:2', 'penalties_due' => 'decimal:2', 'principal_paid' => 'decimal:2', 'interest_paid' => 'decimal:2', 'fees_paid' => 'decimal:2', 'penalties_paid' => 'decimal:2'];
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(RepaymentAllocation::class);
    }

    public function penaltyCharges(): HasMany
    {
        return $this->hasMany(LoanPenaltyCharge::class);
    }

    public function outstandingFor(string $component): int
    {
        $this->guardComponent($component);

        return $this->toMinorUnits($this->getAttribute("{$component}_due"))
            - $this->toMinorUnits($this->getAttribute("{$component}_paid"));
    }

    public function applyAllocation(string $component, int $minorUnits): void
    {
        $this->guardComponent($component);

        if ($minorUnits < 1 || $minorUnits > $this->outstandingFor($component)) {
            throw new InvalidArgumentException('Allocation exceeds the outstanding installment component.');
        }

        $paidColumn = "{$component}_paid";
        $newPaidAmount = $this->toMinorUnits($this->getAttribute($paidColumn)) + $minorUnits;

        $this->setAttribute($paidColumn, $this->fromMinorUnits($newPaidAmount));
        $this->status = $this->isFullyPaid() ? 'Paid' : 'Partially Paid';
        $this->save();
    }

    public function reverseAllocation(string $component, int $minorUnits): void
    {
        $this->guardComponent($component);
        $paidColumn = "{$component}_paid";
        $currentlyPaid = $this->toMinorUnits($this->getAttribute($paidColumn));

        if ($minorUnits < 1 || $minorUnits > $currentlyPaid) {
            throw new InvalidArgumentException('Reversal exceeds the paid installment component.');
        }

        $this->setAttribute($paidColumn, $this->fromMinorUnits($currentlyPaid - $minorUnits));
        $this->status = $currentlyPaid - $minorUnits === 0 && $this->totalPaidMinorUnitsExcept($component) === 0
            ? 'Pending'
            : 'Partially Paid';
        $this->save();
    }

    public function isFullyPaid(): bool
    {
        foreach (['penalties', 'fees', 'interest', 'principal'] as $component) {
            if ($this->outstandingFor($component) > 0) {
                return false;
            }
        }

        return true;
    }

    private function guardComponent(string $component): void
    {
        if (! in_array($component, ['penalties', 'fees', 'interest', 'principal'], true)) {
            throw new InvalidArgumentException("Unsupported repayment component [{$component}].");
        }
    }

    private function totalPaidMinorUnitsExcept(string $excludedComponent): int
    {
        return collect(['penalties', 'fees', 'interest', 'principal'])
            ->reject(fn (string $component): bool => $component === $excludedComponent)
            ->sum(fn (string $component): int => $this->toMinorUnits($this->getAttribute("{$component}_paid")));
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
