<?php

return [
    /*
    | Mức log tối thiểu. Bỏ qua log có level < min_level.
    | 1=debug  2=info  3=warning  4=error  5=critical
    | Production: 2 (info). Dev: 1 (debug).
    */
    'min_level' => (int) env('ACTIVITYLOG_MIN_LEVEL', 2),

    /*
    | Số ngày giữ log trong DB trước khi activitylog:purge xóa.
    */
    'retain_days' => (int) env('ACTIVITYLOG_RETAIN_DAYS', 90),
];
