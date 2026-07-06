<?php
return [
    'queue'                 => env('WORKFLOW_QUEUE', 'workflows'),
    'retain_execution_days' => env('WORKFLOW_RETAIN_DAYS', 60),
    'webhook_timeout'       => env('WORKFLOW_WEBHOOK_TIMEOUT', 15),
    'webhook_max_retries'   => env('WORKFLOW_WEBHOOK_RETRIES', 2),
    'allow_manual_trigger'  => env('WORKFLOW_ALLOW_MANUAL', true),
    'meta_cache_ttl'        => env('WORKFLOW_META_CACHE_TTL', 600),
    'meta_cache_version'    => env('WORKFLOW_META_VERSION', 2),

    /*
    |--------------------------------------------------------------------------
    | Declarative trigger registry
    |--------------------------------------------------------------------------
    | Every entry here becomes a workflow trigger available in the builder UI —
    | no PHP class required. Keys are the trigger `type` (also stored on the
    | workflow row). Per entry:
    |
    |   label   string  Shown in the builder dropdown.
    |   module  string  Group label in the dropdown (and source_module on runs).
    |   event   ?string FQCN of a domain event to auto-bind. When that event fires
    |                   it is mapped into a payload and dispatched. Omit for triggers
    |                   fired imperatively via Workflows::fire().
    |   subject ?string Name of the event property holding the subject model
    |                   (default: first public Eloquent model property).
    |   extra   array   Map of extra-key => event-property for scalar context.
    |                   Omit to auto-collect all scalar event properties.
    |   fields  array   availableFields shown when building conditions. Use keys
    |                   `subject.attr.<col>`, `extra.<key>`, `actor.*`, `subject.*`.
    |   config  array   configFields — per-workflow filter inputs. A non-empty value
    |                   means "only fire when extra.<key> (or subject.attr.<key>)
    |                   equals this value".
    |
    | Adding a new trigger anywhere in the system = add an entry here. That's it.
    */
    'triggers' => [

        // ── Lead ──────────────────────────────────────────────────────────
        'lead.created' => [
            'label'  => 'Lead mới được tạo',
            'module' => 'Lead',
            'event'  => \Modules\Lead\Events\LeadCreated::class,
            'subject'=> 'lead',
            'fields' => [
                ['key' => 'subject.id',                'label' => 'Lead ID',     'type' => 'integer'],
                ['key' => 'subject.attr.source_id',    'label' => 'Nguồn',       'type' => 'integer'],
                ['key' => 'subject.attr.stage_id',     'label' => 'Giai đoạn',   'type' => 'integer'],
                ['key' => 'subject.attr.assigned_to',  'label' => 'Phụ trách',   'type' => 'integer'],
            ],
        ],
        'lead.updated' => [
            'label'  => 'Lead được cập nhật',
            'module' => 'Lead',
            'event'  => \Modules\Lead\Events\LeadUpdated::class,
            'subject'=> 'lead',
            'fields' => [
                ['key' => 'subject.id',            'label' => 'Lead ID',   'type' => 'integer'],
                ['key' => 'subject.attr.stage_id', 'label' => 'Giai đoạn', 'type' => 'integer'],
            ],
        ],
        'lead.stage_changed' => [
            'label'  => 'Lead chuyển giai đoạn',
            'module' => 'Lead',
            'event'  => \Modules\Lead\Events\LeadStageChanged::class,
            'subject'=> 'lead',
            'extra'  => ['from_stage_id' => 'fromStageId', 'to_stage_id' => 'toStageId'],
            'fields' => [
                ['key' => 'subject.id',       'label' => 'Lead ID',           'type' => 'integer'],
                ['key' => 'extra.from_stage_id', 'label' => 'Từ giai đoạn',   'type' => 'integer'],
                ['key' => 'extra.to_stage_id',   'label' => 'Đến giai đoạn',  'type' => 'integer'],
            ],
            'config' => [
                ['key' => 'to_stage_id', 'label' => 'Chỉ khi chuyển đến giai đoạn', 'type' => 'number',
                 'hint' => 'Để trống = mọi giai đoạn'],
            ],
        ],
        'lead.assigned' => [
            'label'  => 'Lead được phân công',
            'module' => 'Lead',
            'event'  => \Modules\Lead\Events\LeadAssigned::class,
            'subject'=> 'lead',
            'fields' => [
                ['key' => 'subject.id',               'label' => 'Lead ID',  'type' => 'integer'],
                ['key' => 'subject.attr.assigned_to', 'label' => 'Phụ trách', 'type' => 'integer'],
            ],
        ],

        // ── Lead Source ───────────────────────────────────────────────────
        'lead_source.created' => [
            'label'  => 'Nguồn lead mới',
            'module' => 'LeadSource',
            'event'  => \Modules\LeadSource\Events\SourceCreated::class,
            'fields' => [
                ['key' => 'subject.id',          'label' => 'Source ID', 'type' => 'integer'],
                ['key' => 'subject.attr.name',   'label' => 'Tên nguồn', 'type' => 'string'],
            ],
        ],

        // ── Lead Pipeline Stage ───────────────────────────────────────────
        'stage.created' => [
            'label'  => 'Giai đoạn pipeline mới',
            'module' => 'LeadPipelineStage',
            'event'  => \Modules\LeadPipelineStage\Events\StageCreated::class,
            'fields' => [
                ['key' => 'subject.id',        'label' => 'Stage ID', 'type' => 'integer'],
                ['key' => 'subject.attr.name', 'label' => 'Tên giai đoạn', 'type' => 'string'],
            ],
        ],

        // ── User ──────────────────────────────────────────────────────────
        'user.created' => [
            'label'  => 'Người dùng mới được tạo',
            'module' => 'User',
            'event'  => \Modules\User\Events\UserCreated::class,
            'subject'=> 'user',
            'fields' => [
                ['key' => 'subject.id',          'label' => 'User ID', 'type' => 'integer'],
                ['key' => 'subject.attr.email',  'label' => 'Email',   'type' => 'string'],
                ['key' => 'subject.attr.name',   'label' => 'Tên',     'type' => 'string'],
            ],
        ],
        'user.role_assigned' => [
            'label'  => 'Người dùng được gán vai trò',
            'module' => 'User',
            'event'  => \Modules\User\Events\UserRoleAssigned::class,
            'fields' => [
                ['key' => 'subject.id', 'label' => 'User ID', 'type' => 'integer'],
            ],
        ],

        // ── Organization ──────────────────────────────────────────────────
        'organization.created' => [
            'label'  => 'Tổ chức mới được tạo',
            'module' => 'Organization',
            'event'  => \Modules\Organization\Events\OrganizationCreated::class,
            'subject'=> 'organization',
            'fields' => [
                ['key' => 'subject.id',        'label' => 'Org ID',   'type' => 'integer'],
                ['key' => 'subject.attr.name', 'label' => 'Tên tổ chức', 'type' => 'string'],
            ],
        ],

        // ── Assessment ────────────────────────────────────────────────────
        'assessment.result_calculated' => [
            'label'  => 'Kết quả Assessment được tính điểm',
            'module' => 'Assessment',
            'event'  => \Modules\Assessment\Events\AssessmentCompleted::class,
            'fields' => [
                ['key' => 'extra.assessment_code', 'label' => 'Assessment Code', 'type' => 'string'],
                ['key' => 'extra.band_code',       'label' => 'Band Code',       'type' => 'string'],
                ['key' => 'extra.overall_score',   'label' => 'Overall Score',   'type' => 'float'],
            ],
            'config' => [
                ['key' => 'band_code', 'label' => 'Band Code cần match', 'type' => 'text',
                 'hint' => 'Để trống = match tất cả band. VD: advanced'],
                ['key' => 'assessment_code', 'label' => 'Assessment Code', 'type' => 'text',
                 'hint' => 'Để trống = match tất cả assessment.'],
            ],
        ],

        // ── Survey (fired imperatively from SubmitSurveyAction) ────────────
        'survey.submitted' => [
            'label'  => 'Khảo sát được gửi',
            'module' => 'Survey',
            'fields' => [
                ['key' => 'extra.survey_id',      'label' => 'Survey ID',       'type' => 'integer'],
                ['key' => 'extra.survey_slug',    'label' => 'Survey slug',     'type' => 'string'],
                ['key' => 'extra.respondent_ref', 'label' => 'Người trả lời',   'type' => 'string'],
            ],
        ],
        'survey.result_calculated' => [
            'label'  => 'Kết quả khảo sát được tính',
            'module' => 'Survey',
            'fields' => [
                ['key' => 'extra.survey_id',     'label' => 'Survey ID',     'type' => 'integer'],
                ['key' => 'extra.band_code',     'label' => 'Band Code',     'type' => 'string'],
                ['key' => 'extra.overall_score', 'label' => 'Overall Score', 'type' => 'float'],
            ],
        ],

        // ── HR ────────────────────────────────────────────────────────────
        'employee.created' => [
            'label'  => 'Nhân viên mới được tạo',
            'module' => 'HR',
            'fields' => [
                ['key' => 'subject.id',                'label' => 'Employee ID',  'type' => 'integer'],
                ['key' => 'subject.attr.full_name',    'label' => 'Họ tên',       'type' => 'string'],
                ['key' => 'subject.attr.department_id','label' => 'Phòng ban',    'type' => 'integer'],
                ['key' => 'subject.attr.position_id',  'label' => 'Chức vụ',     'type' => 'integer'],
            ],
        ],
        'employee.terminated' => [
            'label'  => 'Nhân viên nghỉ việc',
            'module' => 'HR',
            'fields' => [
                ['key' => 'subject.id',                'label' => 'Employee ID',  'type' => 'integer'],
                ['key' => 'subject.attr.full_name',    'label' => 'Họ tên',       'type' => 'string'],
                ['key' => 'extra.termination_reason',  'label' => 'Lý do nghỉ',   'type' => 'string'],
                ['key' => 'extra.last_working_date',   'label' => 'Ngày làm cuối','type' => 'string'],
            ],
        ],

        // ── State Machine (Mô hình B — §6) ────────────────────────────────
        'entity.state_changed' => [
            'label'  => 'Đối tượng đổi trạng thái',
            'module' => 'Core',
            'config_fields' => [
                ['key' => 'entity_type', 'label' => 'Loại đối tượng', 'type' => 'entity_type_select'],
                ['key' => 'from_state',  'label' => 'Từ trạng thái',  'type' => 'state_select',
                 'required' => false, 'hint' => 'Để trống = từ bất kỳ trạng thái'],
                ['key' => 'to_state',    'label' => 'Đến trạng thái', 'type' => 'state_select'],
            ],
            'fields' => [
                ['key' => 'extra.entity_type',    'label' => 'Loại entity',            'type' => 'string'],
                ['key' => 'extra.from_state',     'label' => 'Trạng thái trước',       'type' => 'string'],
                ['key' => 'extra.to_state',       'label' => 'Trạng thái mới',         'type' => 'string'],
                ['key' => 'extra.transition_key', 'label' => 'Transition thực hiện',   'type' => 'string'],
                ['key' => 'extra.comment',        'label' => 'Lý do chuyển trạng thái','type' => 'string'],
            ],
        ],

        // ── Schedule (§6) ─────────────────────────────────────────────────
        'schedule.daily'   => ['label' => 'Hàng ngày',   'module' => 'Schedule'],
        'schedule.weekly'  => ['label' => 'Hàng tuần',   'module' => 'Schedule'],
        'schedule.monthly' => ['label' => 'Hàng tháng',  'module' => 'Schedule'],
        'schedule.hourly'  => ['label' => 'Mỗi giờ',     'module' => 'Schedule'],

        // ── Webhook inbound (§6) ──────────────────────────────────────────
        'webhook.received' => [
            'label'  => 'Webhook từ hệ thống ngoài',
            'module' => 'Core',
            'config_fields' => [
                ['key' => 'source_key', 'label' => 'Nguồn',        'type' => 'text'],
                ['key' => 'secret',     'label' => 'HMAC Secret',   'type' => 'password', 'required' => false],
            ],
            'fields' => [
                ['key' => 'extra.source_key', 'label' => 'Source Key', 'type' => 'string'],
            ],
        ],

        // ── Manual (§6) ──────────────────────────────────────────────────
        'manual' => [
            'label'  => 'Kích hoạt thủ công',
            'module' => 'Core',
        ],
    ],
];
