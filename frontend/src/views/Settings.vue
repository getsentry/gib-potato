<template>
    <div v-if="user">
    <h1 class="text-xl font-semibold leading-6">Settings</h1>
    <div class="divide-y divide-zinc-300 pt-6">
        <div>
            <div>
                <h2 class="text-lg font-medium leading-6">Notifications</h2>
                <p class="mt-1 text-sm text-zinc-500">Set your notification preferences when the <code>@GibPotato</code> Slack bot should notify you.</p>
            </div>
            <ul role="list" class="mt-2 divide-y divide-zinc-200">
                <li class="flex items-center justify-between py-4">
                    <div class="flex flex-col">
                        <p class="text-sm font-medium">Sent Notifications</p>
                        <p class="text-sm text-zinc-500" id="privacy-option-1-description">
                            Receive a notification when you sent someone a potato.
                        </p>
                    </div>
                    <button type="button"
                        class="bg-zinc-200 dark:bg-zinc-600 relative ml-4 inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out"
                        :class="{ '!bg-green-500': user.notifications.sent === true }"
                        @click="toggleSentNotifications"
                    >
                        <span
                            class="translate-x-0 inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                            :class="{ '!translate-x-5': user.notifications.sent === true }"
                        >
                        </span>
                    </button>
                </li>
                <li class="flex items-center justify-between py-4">
                    <div class="flex flex-col">
                        <p class="text-sm font-medium" id="privacy-option-2-label">
                            Received Notifications
                        </p>
                        <p class="text-sm text-zinc-500" id="privacy-option-2-description">
                            Receive a notification when someone sent you a potato.
                        </p>
                    </div>
                    <button type="button"
                        class="bg-zinc-200 dark:bg-zinc-600 relative ml-4 inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out"
                        :class="{ '!bg-green-500': user.notifications.received === true }"
                        @click="toggleReceivedNotifications"
                    >
                        <span
                            class="translate-x-0 inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                            :class="{ '!translate-x-5': user.notifications.received === true }"
                        >
                        </span>
                    </button>
                </li>
                <li class="flex items-center justify-between py-4">
                    <div class="flex flex-col">
                        <p class="text-sm font-medium" id="privacy-option-2-label">
                            Too good to go 🌱
                        </p>
                        <p class="text-sm text-zinc-500" id="privacy-option-2-description">
                            Receive a notification at the end of your work day if you have any leftover potato.
                        </p>
                    </div>
                    <button type="button"
                        class="bg-zinc-200 dark:bg-zinc-600 relative ml-4 inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out"
                        :class="{ '!bg-green-500': user.notifications.too_good_to_go === true }"
                        @click="toggleTooGoodToGoNotifications"
                    >
                        <span
                            class="translate-x-0 inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                            :class="{ '!translate-x-5': user.notifications.too_good_to_go === true }"
                        >
                        </span>
                    </button>
                </li>
            </ul>
        </div>
        </div>
    </div>
    <div v-else-if="isLoading" class="flex justify-center items-center py-8">
        <span class="animate-spin text-2xl">🥔</span>
    </div>
</template>

<script>
import { useUser, useUpdateUser } from '@/composables/useUser'

export default {
    name: 'Settings',
    setup() {
        const { data: user, isLoading } = useUser()
        const updateUser = useUpdateUser()

        const toggleSentNotifications = () => {
            if (user.value) {
                updateUser.mutate({
                    ...user.value,
                    notifications: {
                        ...user.value.notifications,
                        sent: !user.value.notifications.sent
                    }
                })
            }
        }

        const toggleReceivedNotifications = () => {
            if (user.value) {
                updateUser.mutate({
                    ...user.value,
                    notifications: {
                        ...user.value.notifications,
                        received: !user.value.notifications.received
                    }
                })
            }
        }

        const toggleTooGoodToGoNotifications = () => {
            if (user.value) {
                updateUser.mutate({
                    ...user.value,
                    notifications: {
                        ...user.value.notifications,
                        too_good_to_go: !user.value.notifications.too_good_to_go
                    }
                })
            }
        }

        return {
            user,
            isLoading,
            toggleSentNotifications,
            toggleReceivedNotifications,
            toggleTooGoodToGoNotifications
        };
    },
}
</script>
