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
            class="relative -ml-px inline-flex items-center border border-zinc-300  px-4 py-2 text-sm font-medium"
            :class="{ 'bg-amber-200! text-zinc-900!': filter.range === 'year' }"
            @click="updateRangeFilter('year')"
        >
            Last 365 Days
        </button>
        <button
            class="relative -ml-px inline-flex items-center border border-zinc-300  px-4 py-2 text-sm font-medium"
            :class="{ 'bg-amber-200! text-zinc-900!': filter.range === 'quarter' }"
            @click="updateRangeFilter('quarter')"
        >
            Current Quarter
        </button>
        <button
            class="relative -ml-px inline-flex items-center border border-zinc-300  px-4 py-2 text-sm font-medium"
            :class="{ 'bg-amber-200! text-zinc-900!': filter.range === 'month' }"
            @click="updateRangeFilter('month')"
        >
            Last 30 Days
        </button>
        <button
            class="relative -ml-px inline-flex items-center rounded-r-md border border-zinc-300  px-4 py-2 text-sm font-medium"
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
            class="relative -ml-px inline-flex items-center rounded-r-md border border-zinc-300  px-4 py-2 text-sm font-medium"
            :class="{ 'bg-amber-200! text-zinc-900!': filter.order === 'sent' }"
            @click="updateOrderFilter('sent')"
        >
            Sent
        </button>
    </div>
</template>

<script>
import { computed } from 'vue';
import { useStore } from 'vuex';

export default {
    name: 'Filter',
    setup() {
        const store = useStore()
        return {
            filter: computed(() => store.getters.filter)
        }
    },
    methods: {
        updateRangeFilter(range) {
            this.$store.dispatch('setRangeFilter', range)
            this.$store.dispatch('getLeaderboard')
        },
        updateOrderFilter(order) {
            this.$store.dispatch('setOrderFilter', order)
            this.$store.dispatch('getLeaderboard')
        },
    }
};
</script>
