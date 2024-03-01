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
                <div class="relative mt-4">
                    
                    <p class="mt-1 text-sm text-zinc-500">{{ item.message }}</p>

                </div>
            </div>
            <div class="mt-auto">
                <p class="mt-3 text-xs text-zinc-500">
                    
                    Posted {{ new Date(item.created).toLocaleDateString('en-us', { year:"numeric", month:"short", day:"numeric"}) }}
                    
                </p>
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

export default {
    name: 'Quickwins',
    setup() {
        const store = useStore()

        return {
            user: computed(() => store.getters.user),
            taggedMessages: computed(() => store.getters.taggedMessages),
        }
    },
}

</script>
