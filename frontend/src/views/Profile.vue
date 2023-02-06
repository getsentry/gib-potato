<template>
    <h1 class="text-xl font-semibold leading-6">Profile</h1>
    <div class="pt-6">
        <h2 class="text-lg font-medium leading-6">Hey {{ user.slack_name }} 👋</h2>
        <p class="mt-1 text-sm text-zinc-500">I hope you're having a potastic day!</p>
    </div>

    <div class="mt-2">
        <div class="py-4">
            <h2 class="text-lg font-medium leading-6">Your Potato Stats</h2>
            <p class="mt-1 text-sm text-zinc-500">
                You did gib <strong>{{ user.sent_count }}</strong> 🥔 and received <strong>{{ user.received_count }}</strong> 🥔 since you started potatoing
                <strong>{{ new Date(user.created).toLocaleDateString('en-us', { year:"numeric", month:"short", day:"numeric"}) }}.</strong>
            </p>
        </div>
        <div class="py-4">
            <h2 class="text-lg font-medium leading-6">Your activity in the last 30 days</h2>
            <ul class="divide-y divide-zinc-300">
                <li
                    v-for="(message, index) in messages"
                    :key="index"
                    class="py-4"
                >
                    <div class="flex space-x-3">
                        <template v-if="message.sender_user_id === user.id">
                            <img
                                class="h-6 w-6 rounded-full"
                                :src="message.received_user.slack_picture"
                            >
                            <div class="flex-1 space-y-1">
                            <div class="flex items-center justify-between">
                                <h3 class="text-sm font-medium">
                                    To {{ message.received_user.slack_name }}
                                </h3>
                                <p class="text-sm text-gray-500">
                                    {{ new Date(message.created).toLocaleDateString('en-us', { year:"numeric", month:"short", day:"numeric"}) }}
                                    -
                                    {{ new Date(message.created).toLocaleTimeString('en-us', { hour: '2-digit', minute: '2-digit' }) }}
                                </p>
                            </div>
                            <p class="text-sm text-zinc-500">
                                You sent <strong>{{ message.amount }}</strong> 🥔 to {{ message.received_user.slack_name }}
                            </p>
                        </div>
                        </template>
                        <template v-if="message.receiver_user_id === user.id">
                            <img
                                class="h-6 w-6 rounded-full"
                                :src="message.sent_user.slack_picture"
                            >
                            <div class="flex-1 space-y-1">
                            <div class="flex items-center justify-between">
                                <h3 class="text-sm font-medium">
                                    From {{ message.sent_user.slack_name }}
                                </h3>
                                <p class="text-sm text-gray-500">
                                    {{ new Date(message.created).toLocaleDateString('en-us', { year:"numeric", month:"short", day:"numeric"}) }}
                                    -
                                    {{ new Date(message.created).toLocaleTimeString('en-us', { hour: '2-digit', minute: '2-digit' }) }}
                                </p>
                            </div>
                            <p class="text-sm text-zinc-500">
                                You received <strong>{{ message.amount }}</strong> 🥔 from {{ message.sent_user.slack_name }}
                            </p>
                        </div>
                        </template>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</template>

<script>
import { computed } from 'vue'
import { useStore } from 'vuex'

import api from '@/api'

export default {
    name: 'Profile',
    setup() {
        const store = useStore()

        return {
            user: computed(() => store.getters.user),
        };
    },
    data() {
        return {
            messages: []
        }
    },
    mounted() {
        this.fetchMessages()
    },
    methods: {
        async fetchMessages() {
            try {
                const response = await api.get('user/profile')
                this.messages = response.data
            } catch (error) {
                console.log(error)
            }
        },
    }
}

</script>
