@extends('layouts.app')
@section('title', 'Tambah Kategori')
@section('page-title', 'Tambah Kategori')
@section('content')
<div class="py-4 max-w-lg">
    <x-card class="p-6">
        <form action="{{ route('admin.categories.store') }}" method="POST" class="space-y-5">
            @csrf
            @include('admin.categories._form', ['category' => null])
            <div class="flex gap-3"><button type="submit" class="btn-primary">Simpan</button><a href="{{ route('admin.categories.index') }}" class="btn-secondary">Batal</a></div>
        </form>
    </x-card>
</div>
@endsection
