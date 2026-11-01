<?php

namespace App\Http\Requests\Api;

/** Submission may carry the final answers in the same request (optional). */
class SubmitAttemptRequest extends SaveAnswersRequest
{
    protected function answersRequired(): bool
    {
        return false;
    }
}
