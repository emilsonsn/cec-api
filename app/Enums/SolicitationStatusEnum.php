<?php

namespace App\Enums;

enum SolicitationStatusEnum: string
{
    case Pending = 'Pending';
    case Finished = 'Finished';
    case Rejected = 'Rejected';
}
