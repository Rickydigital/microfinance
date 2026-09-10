@extends('layouts.app')
@section('title', 'Notifications')
@section('content')
    <div class="d-flex justify-content-between mb-4"><h1 class="h3">Notifications</h1><form method="POST" action="{{ route('notifications.read') }}">@csrf<button class="btn btn-outline-primary">Mark all read</button></form></div>
    <div class="card border-0 shadow-sm"><div class="list-group list-group-flush">@forelse ($notifications as $notification)<div class="list-group-item py-3 {{ $notification->read_at ? '' : 'bg-light' }}"><strong>{{ $notification->data['subject'] ?? 'Notification' }}</strong><p class="mb-1">{{ $notification->data['message'] ?? '' }}</p><small class="text-muted">{{ $notification->created_at->diffForHumans() }}</small></div>@empty<div class="p-5 text-center text-muted">No notifications.</div>@endforelse</div><div class="card-body">{{ $notifications->links() }}</div></div>
@endsection
