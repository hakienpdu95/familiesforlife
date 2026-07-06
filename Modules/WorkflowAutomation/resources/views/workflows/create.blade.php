@extends('layouts.backend')
@section('title', 'Tạo Workflow')


@section('content')
@include('workflowautomation::workflows._form', ['workflow' => null, 'formAction' => route('workflows.store'), 'method' => 'POST'])
@endsection
