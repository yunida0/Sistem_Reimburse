@extends('layouts.app')
@section('title', 'Edit Kategori')
@section('page-title', 'Edit Kategori')
@section('content')
<div class="py-4 max-w-lg">
    <x-card class="p-6">
        <form action="{{ route('admin.categories.update', $category) }}" method="POST" class="space-y-5">
            @csrf @method('PUT')
            @include('admin.categories._form', compact('category'))
            <div class="flex gap-3"><button type="submit" class="btn-primary">Perbarui</button><a href="{{ route('admin.categories.index') }}" class="btn-secondary">Batal</a></div>
        </form>
    </x-card>
</div>
@endsection
