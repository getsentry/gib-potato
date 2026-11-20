<template>
    <div class="isolate flex md:inline-flex rounded-md mb-4 md:mb-8 md:mr-8">
        <button
            class="relative inline-flex items-center rounded-l-md border border-zinc-300 px-4 py-2 text-sm font-medium"
            :class="{ 'bg-amber-200! text-zinc-900!': filter.range === 'all' }"
            @click="updateRangeFilter('all')"
        >
            All Time
        </button>
        <button
            class="relative -ml-px inline-flex items-center border border-zinc-300 px-4 py-2 text-sm font-medium"
            :class="{ 'bg-amber-200! text-zinc-900!': filter.range === 'year' }"
            @click="updateRangeFilter('year')"
        >
            Last 365 Days
        </button>
        <button
            class="relative -ml-px inline-flex items-center border border-zinc-300 px-4 py-2 text-sm font-medium"
            :class="{ 'bg-amber-200! text-zinc-900!': filter.range === 'quarter' }"
            @click="updateRangeFilter('quarter')"
        >
            Current Quarter
        </button>
        <button
            class="relative -ml-px inline-flex items-center border border-zinc-300 px-4 py-2 text-sm font-medium"
            :class="{ 'bg-amber-200! text-zinc-900!': filter.range === 'month' }"
            @click="updateRangeFilter('month')"
        >
            Last 30 Days
        </button>
        <button
            class="relative -ml-px inline-flex items-center rounded-r-md border border-zinc-300 px-4 py-2 text-sm font-medium"
            :class="{ 'bg-amber-200! text-zinc-900!': filter.range === 'week' }"
            @click="updateRangeFilter('week')"
        >
            Last 7 Days
        </button>
    </div>
    <div class="isolate flex md:inline-flex rounded-md mb-4 md:mb-8">
        <button
            class="relative inline-flex items-center rounded-l-md border border-zinc-300 px-4 py-2 text-sm font-medium"
            :class="{ 'bg-amber-200! text-zinc-900!': filter.order === 'received' }"
            @click="updateOrderFilter('received')"
        >
            Received
        </button>
        <button
            class="relative -ml-px inline-flex items-center rounded-r-md border border-zinc-300 px-4 py-2 text-sm font-medium"
            :class="{ 'bg-amber-200! text-zinc-900!': filter.order === 'sent' }"
            @click="updateOrderFilter('sent')"
        >
            Sent
        </button>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useStore } from 'vuex';

const store = useStore();

const filter = computed(() => store.getters.filter);

function updateRangeFilter(range) {
    store.dispatch('setRangeFilter', range);
    store.dispatch('getLeaderboard');
}

function updateOrderFilter(order) {
    store.dispatch('setOrderFilter', order);
    store.dispatch('getLeaderboard');
}
</script>
