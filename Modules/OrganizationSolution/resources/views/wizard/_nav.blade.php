@php
$steps = [
    'capabilities' => 'Capabilities',
    'workflows'    => 'Workflows',
    'checklists'   => 'Checklists',
    'resources'    => 'Resources',
    'ai'           => 'AI',
    'roles'        => 'Roles',
    'dashboard'    => 'Dashboard',
    'review'       => 'Review',
];
@endphp
<div class="flex flex-wrap gap-2 mb-5">
    @foreach($steps as $key => $label)
    <a href="{{ route('organization_solutions.wizard.' . $key . '.form', $organizationSolution) }}"
       class="btn btn-xs {{ request()->routeIs('*.wizard.' . $key . '.form') ? 'btn-primary' : 'btn-ghost' }}">
        {{ $loop->iteration }}. {{ $label }}
    </a>
    @endforeach
</div>
