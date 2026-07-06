<?php

namespace Whilesmart\Forms\Enums;

enum SubmissionStatus: string
{
    case Pending = 'pending';
    case Processed = 'processed';
    case Failed = 'failed';
}
