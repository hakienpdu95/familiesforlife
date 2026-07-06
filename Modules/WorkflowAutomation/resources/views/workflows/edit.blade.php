@extends('layouts.backend')
@section('title', 'Sửa: ' . $workflow->name)


@section('content')
@include('workflowautomation::workflows._form', ['workflow' => $workflow, 'formAction' => route('workflows.update', $workflow), 'method' => 'PUT'])
@endsection
