<template>
    <div
        v-if="taggedMessages.length"
        class="grid grid-cols-1 gap-y-12 sm:grid-cols-2 sm:gap-x-6 lg:grid-cols-4 xl:gap-x-8 mb-32"
    >
        <div
            v-for="(item, index) in taggedMessages"
            class="h-full flex flex-col"
        >
            <div
                :index="index"
                class="relative"
            >
                <div class="flex items-start gap-2.5">
                    <img class="w-8 h-8 rounded-full" :src="item.user.slack_picture" :alt="item.user.slack_name">
                    <div class="flex flex-col w-full max-w-[320px] leading-1.5 p-4 border-gray-200 bg-gray-100 rounded-e-xl rounded-es-xl dark:bg-gray-700">
                        <div class="flex items-center space-x-2 rtl:space-x-reverse">
                            <span class="text-sm font-semibold text-gray-900 dark:text-white">{{item.user.slack_name}}</span>
                            <span class="text-sm font-normal text-gray-500 dark:text-gray-400">{{ new Date(item.created).toLocaleDateString('en-us', { year:"numeric", month:"short", day:"numeric"}) }}</span>
                        </div>
                        <FormattedMessage :message="item.message" />
                        <span v-if="item.reaction_count >= 2" class="relative text-sm font-normal text-gray-500 dark:text-gray-400">
                            <span v-for="(i, index2) in item.reaction_count" :key="i" :class="index2 === 0 ? '' : '-ml-2'">🥔</span>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div
        v-else
        class="absolute inset-0 flex items-center justify-center"
    >
        <h1 class="text-2xl font-extrabold">
            No #quickwins recorded yet
        </h1>
    </div>
</template>

<script>
import { computed } from 'vue'
import { useStore } from 'vuex'
import FormattedMessage from '../components/FormattedMessage.vue';

export default {
    name: 'Quickwins',
    components: {
        FormattedMessage,
    },
    setup() {
        const store = useStore()

        return {
            taggedMessages: computed(() => store.getters.taggedMessages),
        }
    },
}

</script>
