@extends('layouts.backend')
@section('title', 'Review — ' . $organizationSolution->name)

@section('content')
<div>
    @foreach(['success','error'] as $type)
        @if(session($type))
        <div class="alert alert-{{ $type }} mb-4 text-sm"><span>{{ session($type) }}</span></div>
        @endif
    @endforeach
    @if ($errors->any())
    <div class="alert alert-error mb-4 text-sm">
        <ul class="list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    <h1 class="text-2xl font-bold text-base-content mb-1">{{ $organizationSolution->name }}</h1>
    <p class="text-sm text-base-content/50 mb-4">Bước 8: kiểm tra điều kiện Pre-Deploy trước khi đánh dấu sẵn sàng (ready).</p>

    @include('organizationsolution::wizard._nav')

    <div class="card bg-base-100 shadow-sm border border-base-200 mb-4">
        <div class="card-body p-4">
            <h2 class="font-bold text-sm mb-3">Điều kiện Pre-Deploy (A07 §14)</h2>
            <ul class="space-y-2">
                @foreach ($result['criteria'] as $criterion)
                <li class="flex items-center gap-2 text-sm">
                    @if ($criterion['passed'])
                    <span class="text-success">✓</span>
                    @else
                    <span class="text-error">✗</span>
                    @endif
                    {{ $criterion['label'] }}
                </li>
                @endforeach
            </ul>
        </div>
    </div>

    <form method="POST" action="{{ route('organization_solutions.wizard.review', $organizationSolution) }}">
        @csrf
        <button type="submit" class="btn btn-primary btn-sm" @disabled(!$result['ready'])>
            Đánh dấu sẵn sàng (Ready)
        </button>
    </form>
</div>
@endsection
