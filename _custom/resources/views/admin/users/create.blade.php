@extends('layouts.app')
@section('title', 'Tambah Pengguna')
@section('page-title', 'Tambah Pengguna')

@section('content')
<div class="py-4 max-w-2xl">
    <x-card class="p-6">
        <form action="{{ route('admin.users.store') }}" method="POST" class="space-y-5">
            @csrf
            @include('admin.users._form', ['user' => null])
            <div class="flex gap-3">
                <button type="submit" class="btn-primary">Simpan</button>
                <a href="{{ route('admin.users.index') }}" class="btn-secondary">Batal</a>
            </div>
        </form>
    </x-card>
</div>
@endsection
