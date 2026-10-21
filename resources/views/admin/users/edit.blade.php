@extends('layouts.app')
@section('title', 'Edit Pengguna')
@section('page-title', 'Edit Pengguna')

@section('content')
<div class="py-4 max-w-2xl">
    <x-card class="p-6">
        <form action="{{ route('admin.users.update', $user) }}" method="POST" class="space-y-5">
            @csrf @method('PUT')
            @include('admin.users._form', compact('user'))
            <div class="flex gap-3">
                <button type="submit" class="btn-primary">Perbarui</button>
                <a href="{{ route('admin.users.index') }}" class="btn-secondary">Batal</a>
            </div>
        </form>
    </x-card>
</div>
@endsection
