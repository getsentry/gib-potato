<template>
    <div class="isolate flex md:inline-flex rounded-md mb-4 md:mb-8 md:mr-8">
        <button
            class="relative inline-flex items-center rounded-l-md border border-zinc-300 px-4 py-2 text-sm font-medium"
            :class="{ '!bg-amber-200 !text-zinc-900': rangeFilter === 'all' }"
            @click="setRangeFilter('all')"
        >
            All Time
        </button>
        <button
            class="relative -ml-px inline-flex items-center border border-zinc-300  px-4 py-2 text-sm font-medium"
            :class="{ '!bg-amber-200 !text-zinc-900': rangeFilter === 'year' }"
            @click="setRangeFilter('year')"
        >
            Last 365 Days
        </button>
        <button
            class="relative -ml-px inline-flex items-center border border-zinc-300  px-4 py-2 text-sm font-medium"
            :class="{ '!bg-amber-200 !text-zinc-900': rangeFilter === 'quarter' }"
            @click="setRangeFilter('quarter')"
        >
            Current Quarter
        </button>
        <button
            class="relative -ml-px inline-flex items-center border border-zinc-300  px-4 py-2 text-sm font-medium"
            :class="{ '!bg-amber-200 !text-zinc-900': rangeFilter === 'month' }"
            @click="setRangeFilter('month')"
        >
            Last 30 Days
        </button>
        <button
            class="relative -ml-px inline-flex items-center rounded-r-md border border-zinc-300  px-4 py-2 text-sm font-medium"
            :class="{ '!bg-amber-200 !text-zinc-900': rangeFilter === 'week' }"
            @click="setRangeFilter('week')"
        >
            Last 7 Days
        </button>
    </div>
    <div class="isolate flex md:inline-flex rounded-md mb-4 md:mb-8">
        <button
            class="relative inline-flex items-center rounded-l-md border border-zinc-300 px-4 py-2 text-sm font-medium"
            :class="{ '!bg-amber-200 !text-zinc-900': orderFilter === 'received' }"
            @click="setOrderFilter('received')"
        >
            Received
        </button>
        <button
            class="relative -ml-px inline-flex items-center rounded-r-md border border-zinc-300  px-4 py-2 text-sm font-medium"
            :class="{ '!bg-amber-200 !text-zinc-900': orderFilter === 'sent' }"
            @click="setOrderFilter('sent')"
        >
            Sent
        </button>
    </div>
</template>

<script>
import { useFilters } from '@/composables/useFilters'

export default {
    name: 'Filter',
    setup() {
        const { rangeFilter, orderFilter, setRangeFilter, setOrderFilter } = useFilters()
        
        return {
            rangeFilter,
            orderFilter,
            setRangeFilter,
            setOrderFilter
        }
    },
};
</script>
