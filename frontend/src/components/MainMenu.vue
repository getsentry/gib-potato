<template>
    <nav class="bg-amber-200 relative z-10">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex h-16 items-center justify-between">
                <div class="flex items-center">
                    <div class="flex-shrink-0 font-bold text-zinc-900">
                        <a href="/">
                            GibPotato
                        </a>
                    </div>
                    <div class="hidden sm:ml-6 sm:block">
                        <div class="flex space-x-4">
                            <RouterLink
                                to="/"
                                class="rounded-md px-3 py-2 text-sm font-medium text-zinc-900"
                                :class="{ '!bg-zinc-900 !text-zinc-50': $route.path === '/' }"
                            >
                                Home
                            </RouterLink>
                            <RouterLink
                                to="/stonks"
                                class="rounded-md px-3 py-2 text-sm font-medium text-zinc-900"
                                :class="{ '!bg-zinc-900 !text-zinc-50': $route.path === '/stonks' }"
                            >
                                Stonks
                            </RouterLink>
                            <RouterLink
                                to="/shop"
                                class="rounded-md px-3 py-2 text-sm font-medium text-zinc-900"
                                :class="{ '!bg-zinc-900 !text-zinc-50': $route.path === '/shop' }"
                            >
                                Shop
                            </RouterLink>
                            <RouterLink
                                to="/collection"
                                class="rounded-md px-3 py-2 text-sm font-medium text-zinc-900"
                                :class="{ '!bg-zinc-900 !text-zinc-50': $route.path === '/collection' }"
                            >
                                Collection
                            </RouterLink>
                            <RouterLink
                                to="/quick-wins"
                                class="rounded-md px-3 py-2 text-sm font-medium text-zinc-900"
                                :class="{ '!bg-zinc-900 !text-zinc-50': $route.path === '/quick-wins' }"
                            >
                                Quick Wins
                            </RouterLink>
                        </div>
                    </div>
                </div>
                <div class="hidden sm:ml-6 sm:block">
                    <div class="flex items-center">
                        <div class="relative ml-3">
                            <div>
                                <button
                                    class="flex rounded-full text-sm"
                                    @click="menuOpen = !menuOpen"
                                >
                                    <img class="h-8 w-8 rounded-full" :src="user.slack_picture">
                                </button>
                            </div>
                            <div v-show="menuOpen === true"
                                class="absolute right-0 z-10 mt-2 w-48 origin-top-right rounded-md bg-zinc-50 dark:bg-zinc-800 py-1 shadow-lg ring-1 ring-zinc-900 ring-opacity-5 focus:outline-none">
                                <RouterLink to="/profile" class="block px-4 py-2 text-sm"
                                    :class="{ 'bg-zinc-200 dark:bg-zinc-900': $route.path === '/profile' }">
                                    Your Profile
                                </RouterLink>
                                <RouterLink
                                    to="/settings"
                                    class="block px-4 py-2 text-sm"
                                    :class="{ 'bg-zinc-200 dark:bg-zinc-900': $route.path === '/settings' }"
                                >
                                    Settings
                                </RouterLink>
                                <a href="/logout" class="block px-4 py-2 text-sm">
                                    Sign out
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="-mr-2 flex sm:hidden">
                    <button class="inline-flex items-center justify-center rounded-md p-2 text-zinc-900"
                        @click="menuOpen = !menuOpen">
                        <span v-show="menuOpen === false" class="block h-6 w-6">
                            🍔
                        </span>
                        <svg v-show="menuOpen === true" class="block h-6 w-6" xmlns="http://www.w3.org/2000/svg"
                            fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile menu, show/hide based on menu state. -->
        <div v-show="menuOpen === true" class="sm:hidden">
            <div class="space-y-1 px-2 pt-2 pb-3">
                <RouterLink
                    to="/"
                    class="block rounded-md px-3 py-2 text-base font-medium text-zinc-900"
                    :class="{ '!bg-zinc-900 !text-zinc-50': $route.path === '/' }"
                >
                    Home
                </RouterLink>
                <RouterLink
                    to="/stonks"
                    class="block rounded-md px-3 py-2 text-base font-medium text-zinc-900"
                    :class="{ '!bg-zinc-900 !text-zinc-50': $route.path === '/stonks' }"
                >
                    Stonks
                </RouterLink>
                <RouterLink
                    to="/shop"
                    class="block rounded-md px-3 py-2 text-base font-medium text-zinc-900"
                    :class="{ '!bg-zinc-900 !text-zinc-50': $route.path === '/shop' }"
                >
                    Shop
                </RouterLink>
                <RouterLink
                    to="/collection"
                    class="block rounded-md px-3 py-2 text-base font-medium text-zinc-900"
                    :class="{ '!bg-zinc-900 !text-zinc-50': $route.path === '/collection' }"
                >
                    Collection
                </RouterLink>
                <RouterLink
                    to="/quick-wins"
                    class="block rounded-md px-3 py-2 text-base font-medium text-zinc-900"
                    :class="{ '!bg-zinc-900 !text-zinc-50': $route.path === '/quick-wins' }"
                >
                    Quick Wins
                </RouterLink>
            </div>
            <div class="border-t border-zinc-900 pt-4 pb-3">
                <div class="flex items-center px-5">
                    <div class="flex-shrink-0">
                        <img class="h-10 w-10 rounded-full" :src="user.slack_picture">
                    </div>
                    <div class="ml-3">
                        <div class="text-base font-medium text-zinc-900">{{ user.slack_name }}</div>
                        <div class="text-sm text-zinc-700">Joined {{ new Date(user.created).toLocaleDateString('en-us', { year:"numeric", month:"short", day:"numeric"}) }}</div>
                    </div>
                </div>
                <div class="mt-3 space-y-1 px-2">
                    <RouterLink to="/profile" class="block rounded-md px-3 py-2 text-base font-medium text-zinc-900"
                        :class="{ '!bg-zinc-900 !text-zinc-50': $route.path === '/profile' }">
                        Your Profile
                    </RouterLink>
                    <RouterLink
                        to="/settings"
                        class="block rounded-md px-3 py-2 text-base font-medium text-zinc-900"
                        :class="{ '!bg-zinc-900 !text-zinc-50': $route.path === '/settings' }"
                    >
                        Settings
                    </RouterLink>
                    <a href="/logout" class="block rounded-md px-3 py-2 text-base font-medium text-zinc-900">
                        Sign out
                    </a>
                </div>
            </div>
        </div>
    </nav>
</template>

<script>
import { computed } from 'vue'
import { useStore } from 'vuex'
import { RouterLink } from 'vue-router'

export default {
    name: 'Menu',
    components: { RouterLink },
    setup() {
        const store = useStore()

        return {
            user: computed(() => store.getters.user),
        };
    },
    watch: {
        $route(to, from) {
            this.menuOpen = false
        }
    },
    data() {
        return {
            menuOpen: false,
        };
    },
}
</script>
