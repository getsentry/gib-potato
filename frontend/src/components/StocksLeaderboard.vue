<template>
    <div class="flex items-center">
        <h2 class="text-lg font-medium leading-6">
            Your current balance is {{ user.spendable_count ?? 0 }} 🥔
        </h2>
        <span class="mx-4">-</span>
        <span>Out of potato?</span>
        <a
            href="/gib-credit"
            class="ml-4 flex justify-center rounded-md border border-zinc-300 px-3 py-1 text-sm"
        >
            Gib Credit
        </a>
    </div>

    <table class="mt-16 w-full table-auto divide-y divide-zinc-300 mb-32">
        <thead>
            <tr>
                <th scope="col" class="py-3.5 pr-3 text-left text-sm font-semibold">
                    Rank
                </th>
                <th scope="col" class="py-3.5 px-3 text-left text-sm font-semibold">
                    Person
                </th>
                <th scope="col" class="relative py-3.5 pl-3 text-right text-sm font-semibold">
                    Stock Gain/Losses
                </th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            <tr v-for="(user, index) in leaderboard.stocks" :key="user.id">
                <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm">
                    {{ index + 1 }}
                </td>
                <td class="whitespace-nowrap py-4 px-3 text-sm">
                    <div class="flex items-center">
                        <img class="h-10 w-10 rounded-full mr-4" :src="user.slack_picture" />
                        <span class="overflow-hidden text-ellipsis">
                            {{ user.slack_name }}
                        </span>
                    </div>
                </td>
                <td class="whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm">
                    <template v-if="user.stocks > 0">
                        <span class="ml-4 text-green-500">
                            +{{ user.stocks }}
                        </span>
                    </template>
                    <template v-else-if="user.stocks < 0">
                        <span class="ml-4 text-red-500">
                            {{ user.stocks }}
                        </span>
                    </template>
                    <template v-else>
                        <span class="ml-4 text-zinc-500">
                            0
                        </span>
                    </template>
                </td>
            </tr>
        </tbody>
    </table>
</template>

<script>
import { computed } from 'vue';
import { useStore } from 'vuex';

export default {
    name: 'StocksLeaderboard',
    setup() {
        const store = useStore()

        return {
            user: computed(() => store.getters.user),
            leaderboard: computed(() => store.getters.leaderboard),
        }
    },
};
</script>
