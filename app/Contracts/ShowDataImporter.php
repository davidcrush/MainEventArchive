<?php

namespace App\Contracts;

use App\Data\ImportRequest;
use App\Data\ImportResult;

interface ShowDataImporter
{
    public function import(ImportRequest $request): ImportResult;
}
