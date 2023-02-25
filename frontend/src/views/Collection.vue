<template>
    <div
        v-if="user.purchases.length"
        class="grid grid-cols-1 gap-y-12 sm:grid-cols-2 sm:gap-x-6 lg:grid-cols-4 xl:gap-x-8 mb-32"
    >
        <div v-for="(purchase, index) in user.purchases">
            <div
                :index="index"
                class="relative"
            >
                <div class="relative h-72 w-full overflow-hidden rounded-lg">
                    <img class="h-full w-full object-cover object-center" :src="purchase.image_link">
                </div>
                <div class="relative mt-4">
                    <h3 class="text-sm font-medium">{{ purchase.name }}</h3>
                    <p class="mt-1 text-sm text-zinc-500">{{ purchase.description }}</p>
                    <p class="mt-3 text-xs text-zinc-500">
                        Purchased {{ new Date(purchase.created).toLocaleDateString('en-us', { year:"numeric", month:"short", day:"numeric"}) }}
                    </p>
                </div>
            </div>
            <div class="mt-6">
                <button
                    class="relative w-full flex items-center justify-center rounded-md border border-zinc-300 bg-zinc-100 py-2 px-8 text-sm font-medium text-gray-900"
                >
                    Gift it to someone
                </button>
            </div>
        </div>
    </div>
    <div
        v-else
        class="absolute inset-0 flex items-center justify-center"
    >
        <h1 class="text-2xl font-extrabold">You didn't purchase anything yet...</h1>
    </div>
</template>

<script>
import { computed } from 'vue'
import { useStore } from 'vuex'

export default {
    name: 'Collection',
    setup() {
        const store = useStore()

        return {
            user: computed(() => store.getters.user),
        }
    },
}

</script>
