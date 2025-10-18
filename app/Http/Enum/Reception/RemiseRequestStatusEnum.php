<?php

namespace App\Http\Enum\Reception;

enum RemiseRequestStatusEnum :string
{
     case PENDING  = 'pending';
    case ACCEPTED = 'accepted';
    case REJECTED = 'rejected';
    case APPLIED  = 'applied';

    case CANCELED = 'canceled';
}
