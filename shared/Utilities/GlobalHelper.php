<?php

use Carbon\Carbon;
use Ramsey\Uuid\Uuid;
use Shared\Utilities\DeleteResourceHelper;

function dateNow()
{
    return Carbon::now();
}

function resourceData()
{
    return new DeleteResourceHelper;
}

function generateUuid()
{
    return Uuid::uuid4()->toString();
}
