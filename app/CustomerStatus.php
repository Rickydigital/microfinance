<?php

namespace App;

enum CustomerStatus: string
{
    case Draft = 'Draft';
    case PendingVerification = 'Pending Verification';
    case Active = 'Active';
    case Dormant = 'Dormant';
    case Suspended = 'Suspended';
    case Closed = 'Closed';
}
