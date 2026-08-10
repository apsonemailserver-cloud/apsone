<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Attendance Strict Face Recognition Configuration
    |--------------------------------------------------------------------------
    |
    | true  : Mandatory 3-pose NIP face registration & live face matching
    |         (blocks check-in if live face does not match registered NIP photos).
    | false : Basic face detection mode (only verifies presence of human face in frame).
    |
    */

    'face_recognition_strict' => env('FACE_RECOGNITION_STRICT', true),

];
