<?php

namespace App\Enum;

enum OrderVoidReasonEnum: string
{
    case customer_request = 'customer_request';
    case payment_issue = 'payment_issue';
    case incorrect_order = 'incorrect_order';
    case unavailable_item = 'unavailable_item';
    case other = 'other';
}
