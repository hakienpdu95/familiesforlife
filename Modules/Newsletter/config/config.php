<?php

return [
    'name' => 'Newsletter',

    // spec/Newsletter_Technical_Specification.md §7.2
    'resend_segment_id' => env('NEWSLETTER_RESEND_SEGMENT_ID'),
    'from_address'      => env('NEWSLETTER_FROM_ADDRESS', config('mail.from.address')),
    'double_opt_in'     => env('NEWSLETTER_DOUBLE_OPT_IN', false),
];
