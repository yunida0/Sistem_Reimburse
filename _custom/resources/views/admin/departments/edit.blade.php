@extends('layouts.app')
@section('title', 'Edit Departemen')
@section('page-title', 'Edit Departemen')
@section('content')
<div class="py-4 max-w-lg">
    <x-card class="p-6">
        <form action="{{ route('admin.departments.update', $department) }}" method="POST" class="space-y-5">
            @csrf @method('PUT')
            @include('admin.departments._form', compact('department'))
            <div class="flex gap-3"><button type="submit" class="btn-primary">Perbarui</button><a href="{{ route('admin.departments.index') }}" class="btn-secondary">Batal</a></div>
        </form>
    </x-card>
</div>
@endsection
