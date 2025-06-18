<template>
    <div v-if="quickWins && quickWins.length">

        <div>
            <h2 class="text-lg font-medium leading-6">
                Messages containing #quickwin and 🥔 will be immortalized here
            </h2>
        </div>

        <div class="mt-8 grid grid-cols-1 gap-y-12 sm:grid-cols-2 sm:gap-x-6 lg:grid-cols-4 xl:gap-x-8 mb-32">
            
            <div v-for="(item, index) in quickWins">
                <div
                    :index="index"
                    class="flex items-start gap-2.5"
                >
                    <img
                        class="w-8 h-8 rounded-full"
                        :src="item.user.slack_picture"
                    >
                    <div class="p-4 border border-zinc-300 rounded-e-xl rounded-es-xl">
                        <div class="">
                            <div class="text-sm font-semibold">
                                {{item.user.slack_name}}
                            </div>
                            <div class="text-sm font-normal text-zinc-500">
                                {{ new Date(item.created).toLocaleDateString('en-us', { year:"numeric", month:"short", day:"numeric"}) }}
                            </div>
                        </div>
                        <FormattedMessage :message="item.message" />
                    </div>
                </div>
            </div>
        </div>

    </div>

    <div
        v-else-if="quickWins && quickWins.length === 0"
        class="absolute inset-0 flex items-center justify-center"
    >
        <h1 class="text-2xl font-extrabold">
            No #quickwins happened so far...
        </h1>
    </div>

    <div v-else-if="isLoading" class="flex justify-center items-center py-8">
        <span class="animate-spin text-2xl">🥔</span>
    </div>

</template>

<script>
import FormattedMessage from '../components/FormattedMessage.vue'
import { useQuickWins } from '@/composables/useQuickWins'

export default {
    name: 'QuickWins',
    components: {
        FormattedMessage,
    },
    setup() {
        const { data: quickWins, isLoading } = useQuickWins()

        return {
            quickWins,
            isLoading
        }
    },
}

</script>
