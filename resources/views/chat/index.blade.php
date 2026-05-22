@extends('layouts.app')

@section('content')
<div class="h-screen flex flex-col overflow-hidden text-slate-100" 
     x-data="chatApp({
         initialUsers: {{ json_encode($users) }},
         authUserId: {{ auth()->id() }},
         authUserName: '{{ auth()->user()->name }}',
         authUserAvatar: '{{ auth()->user()->avatar_url }}'
     })">

    <!-- Ambient Grid & Glows -->
    <div class="absolute inset-0 bg-[radial-gradient(ellipse_80%_80%_at_50%_-20%,rgba(13,148,136,0.08),rgba(255,255,255,0))] pointer-events-none"></div>

    <div class="flex-1 flex overflow-hidden relative z-10">
        
        <!-- Left Sidebar: Contacts & Search -->
        @include('chat.partials.sidebar')

        <!-- Right Panel: Active Chat Thread -->
        <main class="flex-1 flex flex-col bg-slate-950/20 backdrop-blur-sm h-full"
              :class="{'hidden': activeContact === null, 'flex': activeContact !== null}">
            
            <template x-if="activeContact === null">
                @include('chat.partials.welcome')
            </template>

            <template x-if="activeContact !== null">
                @include('chat.partials.chat-box')
            </template>
            
        </main>

    </div>
</div>

<!-- Load modular Alpine.js Application logic -->
<script src="{{ asset('js/chatApp.js') }}"></script>
@endsection
