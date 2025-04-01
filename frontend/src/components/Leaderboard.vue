<template>
    <table class="w-full table-auto divide-y divide-zinc-300 mb-32">
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
            <tr v-for="(user, index) in leaderboard.users" :key="user.id">
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
</template>

<script>
import { computed } from 'vue';
import { useStore } from 'vuex';

export default {
    name: 'Leaderboard',
    setup() {
        const store = useStore()

        return {
            leaderboard: computed(() => store.getters.leaderboard),
        }
    },
};
</script>
