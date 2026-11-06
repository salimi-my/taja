<?php

return [

    /*
    |--------------------------------------------------------------------------
    | CSV Import Limits
    |--------------------------------------------------------------------------
    |
    | A file is read in a stream, a chunk at a time, so memory use stays flat
    | whatever the size. The web server and PHP must also accept uploads of
    | `max_file_kilobytes` (`upload_max_filesize`, `post_max_size`, and
    | `client_max_body_size` for nginx).
    |
    */

    'max_file_kilobytes' => (int) env('IMPORT_MAX_FILE_KILOBYTES', 1024 * 1024),

    'max_rows' => (int) env('IMPORT_MAX_ROWS', 2_000_000),

    'chunk_size' => (int) env('IMPORT_CHUNK_SIZE', 1000),

    /*
    |--------------------------------------------------------------------------
    | Reported Issues
    |--------------------------------------------------------------------------
    |
    | Only this many errors and warnings are kept in the database and shown on
    | the page. The errors CSV always lists every row with an error.
    |
    */

    'max_listed_issues' => (int) env('IMPORT_MAX_LISTED_ISSUES', 1000),

    /*
    |--------------------------------------------------------------------------
    | Job Timeout
    |--------------------------------------------------------------------------
    |
    | The longest, in seconds, one validation or import job may run. Keep it
    | below the `retry_after` of the queue connection.
    |
    */

    'job_timeout' => (int) env('IMPORT_JOB_TIMEOUT', 7200),

];
