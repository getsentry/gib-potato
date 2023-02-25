<template>
    <div>
        <h2 class="text-lg font-medium leading-6">You can spend up to {{ user.spendable_count ?? 0 }} 🥔</h2>
    </div>

    <div class="mt-8 grid grid-cols-1 gap-y-12 sm:grid-cols-2 sm:gap-x-6 lg:grid-cols-4 xl:gap-x-8 mb-32">
        <div v-for="(product, index) in products">
            <div
                :index="index"
                class="relative"
                :class="{ 'blur-[2px]': product.stock === 0 }"
            >
                <div class="relative h-72 w-full overflow-hidden rounded-lg">
                    <img class="h-full w-full object-cover object-center" :src="product.image_link">
                </div>
                <div class="relative mt-4">
                    <h3 class="text-sm font-medium">{{ product.name }}</h3>
                    <p class="mt-1 text-sm text-zinc-500">{{ product.description }}</p>
                    <p class="mt-3 text-xs text-zinc-500">{{ product.stock }} left in stock</p>
                </div>
                <div class="absolute inset-x-0 top-0 flex h-72 items-end justify-end overflow-hidden rounded-lg p-4">
                    <div class="absolute inset-x-0 bottom-0 h-36 bg-gradient-to-t from-black opacity-50"></div>
                    <p class="relative text-lg font-semibold text-white">🥔 {{ product.price }}</p>
                </div>
            </div>
            <div class="mt-6">
                <template v-if="product.stock > 0">
                    <button
                        class="relative w-full flex items-center justify-center rounded-md border border-zinc-300 bg-zinc-100 py-2 px-8 text-sm font-medium text-gray-900"
                        @click="openModal(product)"
                    >
                        I want this
                    </button>
                </template>
                <template v-else>
                    <button
                        class="relative w-full flex items-center justify-center rounded-md border border-transparent bg-zinc-500 py-2 px-8 text-sm font-medium text-gray-900"
                        disabled
                    >
                        Out of stock
                    </button>
                </template>
            </div>
        </div>
    </div>
    <div v-if="modalOpen === true" class="relative z-10">
        <div class="fixed inset-0 bg-zinc-700 bg-opacity-75"></div>

        <div class="fixed inset-0 z-10 overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-lg bg-zinc-50 dark:bg-zinc-900 px-4 pt-5 pb-4 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-sm sm:p-6">
                    <div>
                        <div>
                            <div v-if="modalError" class="rounded-md bg-red-50 p-4 mb-4">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <!-- Heroicon name: mini/x-circle -->
                                        <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd"
                                                d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <h3 class="text-sm font-medium text-red-800">
                                            {{ modalError }}
                                        </h3>
                                    </div>
                                </div>
                            </div>
                            <div class="relative">
                                <div class="relative h-72 w-full overflow-hidden rounded-lg">
                                    <img class="h-full w-full object-cover object-center" :src="product.image_link">
                                </div>
                                <div class="relative mt-4">
                                    <h3 class="text-sm font-medium">{{ product.name }}</h3>
                                    <p class="mt-1 text-sm text-zinc-500">{{ product.description }}</p>
                                </div>
                                <div
                                    class="absolute inset-x-0 top-0 flex h-72 items-end justify-end overflow-hidden rounded-lg p-4">
                                    <div
                                        class="absolute inset-x-0 bottom-0 h-36 bg-gradient-to-t from-black opacity-50">
                                    </div>
                                </div>
                            </div>
                            <div v-if="purchaseSuccess === false">
                                <fieldset class="mt-4">
                                    <div class="space-y-2">
                                        <div class="flex items-center">
                                            <input
                                                type="radio"
                                                class="h-4 w-4 border-gray-300 text-indigo-600"
                                                :checked="purchaseMode === 'myself'"
                                                @click="purchaseMode = 'myself'"
                                            >
                                            <label class="ml-3 block text-sm font-medium text-zinc-500">
                                                For myself
                                            </label>
                                        </div>
                                        <div class="flex items-center">
                                            <input
                                                type="radio"
                                                class="h-4 w-4 border-gray-300 text-indigo-600"
                                                :checked="purchaseMode === 'someone-else'"
                                                @click="purchaseMode = 'someone-else'"
                                            >
                                            <label class="ml-3 block text-sm font-medium text-zinc-500">
                                                For someone else
                                            </label>
                                        </div>
                                    </div>
                                </fieldset>
                                <div
                                    v-if="purchaseMode === 'someone-else'"
                                    class="mt-3 space-y-3"
                                >
                                    <input
                                        type="email"
                                        class="block w-full rounded-md text-sm p-2 text-zinc-900 border border-zinc-300 ring-offset-zinc-50 dark:ring-offset-zinc-900 focus:border-indigo-500 focus:ring-indigo-500"
                                        placeholder="Search..."
                                    >
                                    <textarea
                                        class="block w-full rounded-md text-sm p-2 text-zinc-900 border border-zinc-300 ring-offset-zinc-50 dark:ring-offset-zinc-900 focus:border-indigo-500 focus:ring-indigo-500"
                                        rows="2"
                                        placeholder="Write a message..."
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                    <div
                        v-if="purchaseSuccess === false"
                        class="mt-5 sm:mt-6 sm:grid sm:grid-flow-row-dense sm:grid-cols-2 sm:gap-3"
                    >
                        <button
                            v-if="user.spendable_count >= product.price"
                            class="inline-flex w-full justify-center rounded-md border border-transparent bg-amber-200 text-zinc-900 px-4 py-2 text-base font-medium sm:col-start-2 sm:text-sm"
                            @click="purchase(product, presentee)"
                        >
                            Pay {{ product.price }} <span class="ml-2" :class="{ 'animate-spin': loading }">🥔</span>
                        </button>
                        <button
                            v-else
                            class="inline-flex w-full justify-center rounded-md border border-transparent bg-zinc-500 text-zinc-900 px-4 py-2 text-base font-medium sm:col-start-2 sm:text-sm"
                            disabled
                        >
                            Not enough 🥔
                        </button>
                        <button
                            class="mt-3 inline-flex w-full justify-center rounded-md border border-zinc-300 bg-zinc-100 px-4 py-2 text-base font-medium text-zinc-900 sm:mt-0 sm:text-sm"
                            :disabled="loading"
                            @click="closeModal"
                        >
                            Cancel
                        </button>
                    </div>
                    <div
                        v-if="purchaseSuccess"
                        class="mt-5 sm:mt-6"
                    >
                        <button
                            class="mt-3 inline-flex w-full justify-center rounded-md border border-zinc-300 bg-zinc-100 px-4 py-2 text-base font-medium text-zinc-900 sm:mt-0 sm:text-sm"
                            :disabled="loading"
                            @click="closeModal"
                        >
                            All set 🚀
                        </button>
                    </div>
                    <div class="mt-5 text-xs text-center text-zinc-500">
                        <a href="/terms" target="_blank" class="underline">Terms & Conditions</a> apply.
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { computed } from 'vue'
import { useStore } from 'vuex'

import api from '@/api'

export default {
    name: 'Shop',
    setup() {
        const store = useStore()

        return {
            user: computed(() => store.getters.user),
            users: computed(() => store.getters.users),
            products: computed(() => store.getters.products),
        }
    },
    data() {
        return {
            product: null,
            presentee: null,
            message: null,
            modalOpen: false,
            modalError: null,
            purchaseMode: 'myself',
            loading: false,
            purchaseSuccess: false,
        }
    },
    methods: {
        openModal(product) {
            this.product = product
            this.modalOpen = true
        },
        closeModal() {
            this.product = null
            this.modalError = null
            this.modalOpen = false
            this.purchaseSuccess = false
            this.purchaseMode = 'myself'
        },
        async purchase() {
            this.loading = true
            this.modalError = null

            try {
                await api.post('shop/purchase', {
                    product_id: this.product.id,
                    presentee_id: this.presentee?.id,
                    message: this.message,
                })
                this.purchaseSuccess = true

                await this.$store.dispatch('getUser')
                await this.$store.dispatch('getProducts')
            } catch (error) {
                console.log(error)
                this.modalError = error.response.data.error
            } finally {
                this.loading = false
            }
        }
    }
}

</script>
