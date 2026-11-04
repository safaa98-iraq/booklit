<?php

namespace App\Http\Controllers\Student\Concerns;

use App\Models\Booklet;
use App\Models\Subject;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;

/**
 * Server-side access checks: paid content is never rendered unless the
 * student's subscription covers it (SubjectLockedException otherwise).
 */
trait GuardsContent
{
    protected function guardSubject(Request $request, Subject $subject): void
    {
        $subject->loadMissing('stage');
        $this->authorize('view', $subject);
        app(SubscriptionService::class)->ensureAccess($request->user(), $subject);
    }

    protected function guardBooklet(Request $request, Booklet $booklet): void
    {
        $booklet->loadMissing('subject.stage');
        $this->authorize('view', $booklet);
        app(SubscriptionService::class)->ensureAccess($request->user(), $booklet->subject);
    }
}
