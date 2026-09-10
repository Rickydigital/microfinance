<?php

namespace App\Data;

use Carbon\CarbonImmutable;
use InvalidArgumentException;

class RepaymentData
{
    public function __construct(
        public readonly int $loanId,
        public readonly int $receivedBy,
        public readonly string $receiptNumber,
        public readonly string $transactionNumber,
        public readonly string $amount,
        public readonly string $paymentMethod,
        public readonly CarbonImmutable $paidAt,
        public readonly ?string $externalReference = null,
        public readonly ?string $idempotencyKey = null,
    ) {
        if ((float) $this->amount <= 0) {
            throw new InvalidArgumentException('Repayment amount must be greater than zero.');
        }
    }
}
