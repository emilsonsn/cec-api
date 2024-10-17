<?php

namespace App\Enums;

enum PurchaseStatusEnum: string
{
    case Pending = 'Pending';
    case Resolved = 'Resolved';
    case RequestFinance = 'RequestFinance';
    case RequestManager = 'RequestManager';
}
