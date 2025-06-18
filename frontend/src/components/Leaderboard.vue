<template>
    <table v-if="leaderboard" class="w-full table-auto divide-y divide-zinc-300 mb-32">
        <thead>
            <tr>
                <th scope="col" class="py-3.5 pr-3 text-left text-sm font-semibold">
                    Rank
                </th>
                <th scope="col" class="py-3.5 px-3 text-left text-sm font-semibold">
                    Person
                </th>
                <th scope="col" class="hidden md:table-cell py-3.5 px-3 text-right text-sm font-semibold">
                    Sent
                </th>
                <th scope="col" class="hidden md:table-cell relative py-3.5 pl-3 text-right text-sm font-semibold">
                    Received
                </th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            <tr v-for="(user, index) in leaderboard" :key="user.id">
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
                <td class="hidden md:table-cell whitespace-nowrap py-4 px-3 text-right text-sm">
                    {{ user.sent_count ?? 0 }}
                </td>
                <td class="hidden md:table-cell relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm">
                    {{ user.received_count ?? 0 }}
                </td>
            </tr>
        </tbody>
    </table>
    <div v-else-if="isLoading" class="flex justify-center items-center py-8">
        <span class="animate-spin text-2xl">🥔</span>
    </div>
    <div v-else-if="isError" class="flex justify-center items-center py-8">
        <p class="text-red-500">Error loading leaderboard</p>
    </div>
</template>

<script>
import { useLeaderboard } from '@/composables/useLeaderboard'
import { useFilters } from '@/composables/useFilters'

export default {
    name: 'Leaderboard',
    setup() {
        const { filters } = useFilters()
        const { data: leaderboard, isLoading, isError } = useLeaderboard(filters)
        
        return {
            leaderboard,
            isLoading,
            isError
        }
    },
};
</script>
