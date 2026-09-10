<?php

namespace App;

enum LoanStatus: string
{
    case PendingDisbursement = 'Pending Disbursement';
    case Active = 'Active';
    case InArrears = 'In Arrears';
    case Restructured = 'Restructured';
    case Settled = 'Settled';
    case WrittenOff = 'Written Off';
    case Closed = 'Closed';
}
