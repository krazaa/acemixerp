<?php

namespace Modules\Expense\Enums;

enum ExpenseClaimStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case ManagerApproved = 'manager_approved';
    case CeoApproved = 'ceo_approved';
    case Reimbursed = 'reimbursed';
    case Rejected = 'rejected';
}
