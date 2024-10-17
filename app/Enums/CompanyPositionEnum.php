<?php

namespace App\Enums;

enum CompanyPositionEnum: string
{
    case Admin = 'Admin';
    case Financial = 'Financial';
    case Supplies = 'Supplies';
    case Requester = 'Requester';
}
