<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('codeforge:about', function () {
    $this->comment('CodeForge Laravel API is ready.');
});
