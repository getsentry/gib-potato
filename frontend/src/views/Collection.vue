<template>
    <div
        v-if="collection && collection.length"
        class="grid grid-cols-1 gap-y-12 sm:grid-cols-2 sm:gap-x-6 lg:grid-cols-4 xl:gap-x-8 mb-32"
    >
        <div
            v-for="(item, index) in collection"
            class="h-full flex flex-col"
        >
            <div
                :index="index"
                class="relative"
            >
                <div class="relative h-72 w-full overflow-hidden rounded-lg">
                    <img class="h-full w-full object-cover object-center" :src="item.image_link">
                </div>
                <div class="relative mt-4">
                    <h3 class="text-sm font-medium">{{ item.name }}</h3>
                    <p class="mt-1 text-sm text-zinc-500">{{ item.description }}</p>

                </div>
            </div>
            <div class="mt-auto">
                <p class="mt-3 text-xs text-zinc-500">
                    <template v-if="item.presentee_id === user?.id">
                        Received {{ new Date(item.created).toLocaleDateString('en-us', { year:"numeric", month:"short", day:"numeric"}) }}
                    </template>
                    <template v-else>
                        Purchased {{ new Date(item.created).toLocaleDateString('en-us', { year:"numeric", month:"short", day:"numeric"}) }}
                    </template>
                </p>
            </div>
        </div>
    </div>
    <div
        v-else-if="collection && collection.length === 0"
        class="absolute inset-0 flex items-center justify-center"
    >
        <h1 class="text-2xl font-extrabold">
            You didn't purchase or receive anything...
        </h1>
    </div>
    <div v-else-if="isLoading" class="flex justify-center items-center py-8">
        <span class="animate-spin text-2xl">🥔</span>
    </div>
</template>

<script>
import { useCollection } from '@/composables/useCollection'
import { useUser } from '@/composables/useUser'

export default {
    name: 'Collection',
    setup() {
        const { data: user } = useUser()
        const { data: collection, isLoading } = useCollection()

        return {
            user,
            collection,
            isLoading
        }
    },
}

</script>
