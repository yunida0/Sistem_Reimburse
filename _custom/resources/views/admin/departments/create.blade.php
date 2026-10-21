@extends('layouts.app')
@section('title', 'Tambah Departemen')
@section('page-title', 'Tambah Departemen')
@section('content')
<div class="py-4 max-w-lg">
    <x-card class="p-6">
        <form action="{{ route('admin.departments.store') }}" method="POST" class="space-y-5">
            @csrf
            @include('admin.departments._form', ['department' => null])
            <div class="flex gap-3"><button type="submit" class="btn-primary">Simpan</button><a href="{{ route('admin.departments.index') }}" class="btn-secondary">Batal</a></div>
        </form>
    </x-card>
</div>
@endsection
