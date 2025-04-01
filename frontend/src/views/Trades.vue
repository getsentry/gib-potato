<template>
    <table
        v-if="trades"
        class="mt-4 mb-36 w-full table-auto divide-y divide-zinc-300"
    >
        <thead>
            <tr>
                <th scope="col" class="py-3.5 pr-3 text-left text-sm font-semibold">
                    #
                </th>
                <th scope="col" class="py-3.5 px-3 text-left text-sm font-semibold">
                    Type
                </th>
                <th scope="col" class="py-3.5 px-3 text-left text-sm font-semibold">
                    Status
                </th>
                <th scope="col" class="py-3.5 px-3 text-right text-sm font-semibold">
                    Price
                </th>
                <th scope="col" class="py-3.5 pl-3 text-right text-sm font-semibold">
                    Time
                </th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            <tr v-for="trade in trades" :key="trade.id">
                <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-bold">
                    {{ trade.symbol }}
                </td>
                <td class="whitespace-nowrap py-4 px-3 text-sm">
                    <span
                        v-if="trade.type === 'buy'"
                        class="text-sm font-medium ml-auto px-2.5 py-0.5 rounded bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300"
                    >
                        {{ trade.type }} order
                    </span>
                    <span
                        v-if="trade.type === 'sell'"
                        class="text-sm font-medium ml-auto px-2.5 py-0.5 rounded bg-teal-100 text-teal-800 dark:bg-teal-900 dark:text-teal-300"
                    >
                        {{ trade.type }} order
                    </span>
                </td>
                <td class="whitespace-nowrap py-4 px-3 text-sm">
                    <span
                        class="text-sm font-medium ml-auto px-2.5 py-0.5 rounded bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300"
                    >
                        {{ trade.status }}
                    </span>
                </td>
                <td class="whitespace-nowrap py-4 px-3 text-right text-sm font-bold">
                    {{ trade.price }}
                </td>
                <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm">
                    {{ trade.time }}
                </td>
            </tr>
        </tbody>
    </table>
</template>

<script>
import { computed } from 'vue'
import { useStore } from 'vuex'

export default {
    name: 'Trades',
    setup() {
        const store = useStore()

        return {
            trades: computed(() => store.getters.trades),
        }
    },
}

</script>
