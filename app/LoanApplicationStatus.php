<?php

namespace App;

enum LoanApplicationStatus: string
{
    case Draft = 'Draft';
    case Submitted = 'Submitted';
    case UnderAssessment = 'Under Assessment';
    case Recommended = 'Recommended';
    case PendingApproval = 'Pending Approval';
    case Approved = 'Approved';
    case Rejected = 'Rejected';
    case ReadyForDisbursement = 'Ready for Disbursement';
    case ConvertedToLoan = 'Converted to Loan';
}
