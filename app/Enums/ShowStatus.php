<?php

namespace App\Enums;

enum ShowStatus: string
{
    case Draft = 'draft';
    case PendingReview = 'pending_review';
    case Published = 'published';
}
