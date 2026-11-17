<?php

namespace App;

enum TransactionStatus: string
{
    case PendingAuthorization = 'Pending Authorization';
    case Posted = 'Posted';
    case Reversed = 'Reversed';
}
