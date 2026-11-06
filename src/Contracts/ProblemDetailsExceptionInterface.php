<?php

declare(strict_types=1);

namespace AlexXVL\ProblemDetails\Contracts;

use AlexXVL\ProblemDetails\ProblemDetails;
use Illuminate\Http\Request;

interface ProblemDetailsExceptionInterface
{
    public function toProblemDetails(Request $request): ProblemDetails;
}
