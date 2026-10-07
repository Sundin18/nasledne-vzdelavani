<?php

return [
    // Keep keys equal to the role "name" stored in DB (see RolePermissionFactory)
    'Requester' => 'Requester',
    'OR Approver' => 'OR Approver',
    'Organization Statement Editor' => 'Organization Statement Editor',
    'Summary Author' => 'Summary Author',
    'Summary Reviewer' => 'Summary Reviewer',
    'Director' => 'Director',
    'System Admin' => 'System Admin',
    'ReadOnly Auditor' => 'Read-only Auditor',

    // Legacy/aliases (kept for compatibility if still present in DB)
    'Section Assessor' => 'Section Assessor',
];
