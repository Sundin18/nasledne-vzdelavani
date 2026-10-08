<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-zinc-50 dark:bg-zinc-800">
        <flux:header container class="border-b border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.toggle class="lg:hidden mr-2" icon="bars-2" inset="left" />

            <x-app-logo href="{{ route('home') }}" wire:navigate />

            <flux:navbar class="-mb-px ms-6 max-lg:hidden">
                <flux:navbar.item icon="calendar-days" :href="route('courses.index')" :current="request()->routeIs('courses.index', 'admin.courses.*')" wire:navigate>
                    Kurzy
                </flux:navbar.item>
                <flux:navbar.item icon="academic-cap" :href="route('courses.mine')" :current="request()->routeIs('courses.mine')" wire:navigate>
                    Moje kurzy
                </flux:navbar.item>
                @can('admin')
                    <flux:navbar.item icon="tag" :href="route('admin.categories.index')" :current="request()->routeIs('admin.categories.*')" wire:navigate>
                        Kategorie
                    </flux:navbar.item>
                @endcan
            </flux:navbar>

            <flux:spacer />

            @can('admin')
                <flux:badge size="sm" color="zinc" class="me-3 max-sm:hidden">Admin</flux:badge>
            @endcan

            <x-desktop-user-menu />
        </flux:header>

        <!-- Mobile Menu -->
        <flux:sidebar collapsible="mobile" sticky class="lg:hidden border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('home') }}" wire:navigate />
                <flux:sidebar.collapse class="in-data-flux-sidebar-on-desktop:not-in-data-flux-sidebar-collapsed-desktop:-mr-2" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.item icon="calendar-days" :href="route('courses.index')" :current="request()->routeIs('courses.index', 'admin.courses.*')" wire:navigate>
                    Kurzy
                </flux:sidebar.item>
                <flux:sidebar.item icon="academic-cap" :href="route('courses.mine')" :current="request()->routeIs('courses.mine')" wire:navigate>
                    Moje kurzy
                </flux:sidebar.item>
                @can('admin')
                    <flux:sidebar.item icon="tag" :href="route('admin.categories.index')" :current="request()->routeIs('admin.categories.*')" wire:navigate>
                        Kategorie
                    </flux:sidebar.item>
                @endcan
            </flux:sidebar.nav>
        </flux:sidebar>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
