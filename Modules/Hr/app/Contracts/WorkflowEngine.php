<?php

// app/Contracts/WorkflowEngine.php

namespace Modules\Hr\Contracts;

use Illuminate\Database\Eloquent\Model;

interface WorkflowEngine
{
    /**
     * Submit a document into the workflow engine.
     */
    public function submit(Model $document, array $context = []): void;

    /**
     * Approve current level and advance.
     */
    public function approve(Model $document, int $approverId, ?string $remarks = null): void;

    /**
     * Reject the document at current level.
     */
    public function reject(Model $document, int $approverId, string $reason): void;

    /**
     * Whether the document has reached final approval.
     */
    public function isFullyApproved(Model $document): bool;
}
