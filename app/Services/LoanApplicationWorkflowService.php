<?php

namespace App\Services;

use App\LoanApplicationStatus;
use App\Models\LoanApplication;
use DomainException;
use Illuminate\Support\Facades\DB;

class LoanApplicationWorkflowService
{
    public function submit(LoanApplication $application): LoanApplication
    {
        return DB::transaction(function () use ($application): LoanApplication {
            $application = LoanApplication::query()->lockForUpdate()->findOrFail($application->id);

            if ($application->status !== LoanApplicationStatus::Draft) {
                throw new DomainException('Only a draft application can be submitted.');
            }

            $application->update([
                'status' => LoanApplicationStatus::Submitted,
                'submitted_at' => now(),
            ]);

            return $application->refresh();
        });
    }
}
