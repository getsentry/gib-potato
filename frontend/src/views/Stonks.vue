<template>

    <div class="flex items-center">
        <h2 class="text-lg font-medium leading-6">
            Your current balance is {{ user.spendable_count ?? 0 }} 🥔
        </h2>
        <span class="mx-4">-</span>
        <span>Out of potato?</span>
        <button class="ml-4 flex justify-center rounded-md border border-zinc-300 px-3 py-1 text-sm">Gib Credit</button>
    </div>

    <div class="mt-16 grid grid-cols-1 gap-y-12 sm:grid-cols-2 sm:gap-x-6 lg:grid-cols-4 xl:gap-x-8">
        <div
            v-for="(stonk, index) in stonks.stonks"
            class="h-full flex flex-col"
        >
            <div
                :index="index"
            >
                <div>
                    <Line :data="stonk.data" :options="chartOptions" />
                </div>
                <div class="mt-4 flex items-center">
                    <div>
                        <h3 class="text-sm font-medium">{{ stonk.symbol }}</h3>
                        <p class="mt-1 text-sm text-zinc-500">
                            {{ stonk.description }}
                        </p>
                    </div>
                    <span class="ml-auto">
                        <span class="text-xl font-semibold text-white">
                            {{ stonk.share_price }}
                        </span>
                        <span
                            class="text-zinc-500 ml-4"
                            :class="{
                                '!text-green-500': stonk.stock_info.amount > 0,
                                '!text-red-500': stonk.stock_info.amount < 0,
                            }"
                        >
                            <span v-if="stonk.stock_info.amount > 0">+</span>{{ stonk.stock_info.amount }}
                        </span>
                    </span>
                </div>
                <div class="mt-4 flex">
                    <div class="flex-1">
                        <div class="flex text-sm">
                            <span class="text-zinc-500">Open</span>
                            <span class="ml-auto font-semibold">{{ stonk.stock_info.open }}</span>
                        </div>
                        <div class="flex text-sm">
                            <span class="text-zinc-500">High</span>
                            <span class="ml-auto font-semibold">{{ stonk.stock_info.high }}</span>
                        </div>
                        <div class="flex text-sm">
                            <span class="text-zinc-500">Low</span>
                            <span class="ml-auto font-semibold">{{ stonk.stock_info.low }}</span>
                        </div>
                    </div>
                    <hr class="mx-2">
                    <div class="flex-1">
                        <div class="flex text-sm">
                            <span class="text-zinc-500">Vol</span>
                            <span class="ml-auto font-semibold">{{ stonk.stock_info.volume }}</span>
                        </div>
                        <div class="flex text-sm">
                            <span class="text-zinc-500">Mkt Cap</span>
                            <span class="ml-auto font-semibold">{{ stonk.stock_info.market_cap }}</span>
                        </div>
                        <div class="flex text-sm">
                            <span class="text-zinc-500">???</span>
                            <span class="ml-auto font-semibold">{{ stonk.stock_info.something }}</span>
                        </div>
                    </div>
                </div>
                <div class="mt-8">
                    <button
                        class="inline-flex w-full justify-center rounded-md border border-transparent bg-amber-200 text-zinc-900 px-4 py-2 text-md"
                        @click="openModal(stonk, 'buy')"
                    >
                        Buy
                    </button>
                    <button
                        class="mt-2 inline-flex w-full justify-center rounded-md border border-zinc-300 px-4 py-2 text-md"
                        @click="openModal(stonk, 'sell')"
                    >
                        Sell
                    </button>
                </div>
            </div>
        </div>
    </div>

    <h2 class="mt-16 text-lg font-medium leading-6">
        Your portfolio
    </h2>
    <div class="mt-4 flex space-x-4">
        <div
            v-for="stock in stonks.portfilio"
            class="flex-1 flex items-center"
        >
            <h3 class="font-medium">{{ stock.symbol }}</h3>
            <span class="ml-2">×{{ stock.count }}</span>
            <span class="ml-auto font-semibold">{{ stock.value }}</span>
        </div>
    </div>

    <h2 class="mt-16 text-lg font-medium leading-6">
        Your order history
    </h2>
    <table class="w-full table-auto divide-y divide-zinc-300 mt-4">
        <thead>
            <tr>
                <th scope="col" class="py-3.5 pr-3 text-left text-sm font-semibold">
                    #
                </th>
                <th scope="col" class="py-3.5 px-3 text-left text-sm font-semibold">
                    Type
                </th>
                <th scope="col" class="py-3.5 px-3 text-left text-sm font-semibold">
                    Status
                </th>
                <th scope="col" class="py-3.5 px-3 text-right text-sm font-semibold">
                    Price
                </th>
                <th scope="col" class="py-3.5 pl-3 text-right text-sm font-semibold">
                    Time
                </th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            <tr v-for="trade in stonks.trades" :key="trade.id">
                <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-bold">
                    {{ trade.symbol }}
                </td>
                <td class="whitespace-nowrap py-4 px-3 text-sm">
                    <span class="text-sm font-medium ml-auto px-2.5 py-0.5 rounded">
                        {{ trade.type }} order
                    </span>
                </td>
                <td class="whitespace-nowrap py-4 px-3 text-sm">
                    <span
                        class="text-sm font-medium ml-auto px-2.5 py-0.5 rounded"
                        :class="{
                            '!bg-green-100 !text-green-800 dark:!bg-green-900 dark:!text-green-300': trade.status === 'executed',
                            '!bg-red-100 !text-red-800 dark:!bg-red-900 dark:!text-red-300': trade.status === 'failed',
                            '!bg-blue-100 !text-blue-800 dark:!bg-blue-900 dark:!text-blue-300': trade.status === 'pending',
                        }"
                    >
                        {{ trade.status }}
                    </span>
                </td>
                <td
                    class="whitespace-nowrap py-4 px-3 text-right text-sm font-bold"
                    :class="{
                        '!text-green-500': trade.type === 'sell',
                        '!text-red-500': trade.type === 'buy',
                    }"
                >
                <span v-if="trade.type === 'sell'">+</span><span v-if="trade.type === 'buy'">-</span>{{ trade.price }}

                </td>
                <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm">
                    {{ trade.time }}
                </td>
            </tr>
        </tbody>
    </table>

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
                            <div>
                                <Line :data="stonk.data" :options="chartOptions" />
                            </div>
                            <div class="mt-4 flex items-center">
                                <div>
                                    <h3 class="text-sm font-medium">{{ stonk.symbol }}</h3>
                                    <p class="mt-1 text-sm text-zinc-500">
                                        {{ stonk.description }}
                                    </p>
                                </div>
                                <span class="ml-auto">
                                    <span class="text-xl font-semibold text-white">
                                        {{ stonk.share_price }}
                                    </span>
                                    <span
                                        class="text-zinc-500 ml-4"
                                        :class="{
                                            '!text-green-500': stonk.stock_info.amount > 0,
                                            '!text-red-500': stonk.stock_info.amount < 0,
                                        }"
                                    >
                                        <span v-if="stonk.stock_info.amount > 0">+</span>{{ stonk.stock_info.amount }}
                                    </span>
                                </span>
                            </div>
                            <div class="mt-4 flex">
                                <div class="flex-1">
                                    <div class="flex text-sm">
                                        <span class="text-zinc-500">Open</span>
                                        <span class="ml-auto font-semibold">{{ stonk.stock_info.open }}</span>
                                    </div>
                                    <div class="flex text-sm">
                                        <span class="text-zinc-500">High</span>
                                        <span class="ml-auto font-semibold">{{ stonk.stock_info.high }}</span>
                                    </div>
                                    <div class="flex text-sm">
                                        <span class="text-zinc-500">Low</span>
                                        <span class="ml-auto font-semibold">{{ stonk.stock_info.low }}</span>
                                    </div>
                                </div>
                                <hr class="mx-2">
                                <div class="flex-1">
                                    <div class="flex text-sm">
                                        <span class="text-zinc-500">Vol</span>
                                        <span class="ml-auto font-semibold">{{ stonk.stock_info.volume }}</span>
                                    </div>
                                    <div class="flex text-sm">
                                        <span class="text-zinc-500">Mkt Cap</span>
                                        <span class="ml-auto font-semibold">{{ stonk.stock_info.market_cap }}</span>
                                    </div>
                                    <div class="flex text-sm">
                                        <span class="text-zinc-500">???</span>
                                        <span class="ml-auto font-semibold">{{ stonk.stock_info.something }}</span>
                                    </div>
                                </div>
                            </div>
                            <div v-if="orderSuccess === false">
                                <input
                                    type="number"
                                    min="1"
                                    max="100"
                                    v-model="amount"
                                    class="mt-8 w-full rounded-md text-sm p-2 text-zinc-900 border border-zinc-300 ring-offset-zinc-50 dark:ring-offset-zinc-900 focus:border-indigo-500 focus:ring-indigo-500"
                                />
                            </div>
                        </div>
                    </div>
                    <div
                        v-if="orderSuccess === false"
                        class="mt-2 sm:mt-3 sm:grid sm:grid-flow-row-dense sm:grid-cols-2 sm:gap-3"
                    >
                        <template v-if="orderMode === 'buy'">
                            <button
                                v-if="user.spendable_count >= stonk.share_price * amount"
                                class="inline-flex w-full justify-center rounded-md border border-transparent bg-amber-200 text-zinc-900 px-4 py-2 text-base font-medium sm:col-start-2 sm:text-sm"
                                @click="order()"
                            >
                                Buy for {{ stonk.share_price * amount }} <span class="ml-2" :class="{ 'animate-spin': loading }">🥔</span>
                            </button>
                            <button
                                v-else
                                class="inline-flex w-full justify-center rounded-md border border-transparent bg-zinc-500 text-zinc-900 px-4 py-2 text-base font-medium sm:col-start-2 sm:text-sm"
                                disabled
                            >
                                Not enough 🥔
                            </button>
                        </template>
                        <template v-if="orderMode === 'sell'">
                            <button
                                v-if="true"
                                class="inline-flex w-full justify-center rounded-md border border-transparent bg-amber-200 text-zinc-900 px-4 py-2 text-base font-medium sm:col-start-2 sm:text-sm"
                                @click="order()"
                            >
                                Sell for {{ stonk.share_price * amount }} <span class="ml-2" :class="{ 'animate-spin': loading }">🥔</span>
                            </button>
                            <button
                                v-else
                                class="inline-flex w-full justify-center rounded-md border border-transparent bg-zinc-500 text-zinc-900 px-4 py-2 text-base font-medium sm:col-start-2 sm:text-sm"
                                disabled
                            >
                                You don't own this many {{ stonk.symbol }}
                            </button>
                        </template>
                        <button
                            class="mt-3 inline-flex w-full justify-center rounded-md border border-zinc-300 bg-zinc-100 px-4 py-2 text-base font-medium text-zinc-900 sm:mt-0 sm:text-sm"
                            :disabled="loading"
                            @click="closeModal"
                        >
                            Cancel
                        </button>
                    </div>
                    <div
                        v-if="orderSuccess"
                        class="mt-5 sm:mt-6"
                    >
                        <button
                            class="mt-3 inline-flex w-full justify-center rounded-md border border-zinc-300 bg-zinc-100 px-4 py-2 text-base font-medium text-zinc-900 sm:mt-0 sm:text-sm"
                            :disabled="loading"
                            @click="closeModal"
                        >
                            Your order was submitted ✅
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

import { Line } from 'vue-chartjs'
import { Chart as ChartJS, CategoryScale, Filler, LineElement, LinearScale, PointElement } from 'chart.js'

ChartJS.register(
  CategoryScale,
  Filler,
  LinearScale,
  PointElement,
  LineElement,
)

export default {
    name: 'Stonks',
    components: {
        Line,
    },
    setup() {
        const store = useStore()

        return {
            user: computed(() => store.getters.user),
            stonks: computed(() => store.getters.stonks),
            chartOptions: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false,
                    },
                    tooltip: {
                        enabled: false,
                    }
                },
                elements: {
                    point: {
                        pointStyle: false,
                    },
                    line: {
                        borderWidth: 1,
                        tension: 0.1,
                        fill: true,
                    }
                },
                scales: {
                    x: {
                        ticks: {
                            color: '#fafafa',
                            minRotation: 0,
                            maxRotation: 0,
                            autoSkipPadding: 8,
                        },
                        grid: {
                            color: 'rgba(113, 113, 122, 0.2)',
                        }
                    },
                    y: {
                        ticks: {
                            color: '#fafafa',
                        },
                        grid: {
                            color: 'rgba(113, 113, 122, 0.2)',
                        }
                    },
                }
            },
        }
    },
    data() {
        return {
            stonk: null,
            orderMode: 'buy',
            orderSuccess: false,
            amount: 1,
            modalOpen: false,
            modalError: null,
            loading: false,
        }
    },
    methods: {
        openModal(stonk, orderMode) {
            this.stonk = stonk
            this.orderMode = orderMode
            this.modalOpen = true
        },
        closeModal() {
            this.stonk = null
            this.orderMode = 'buy'
            this.orderSuccess = false
            this.amount = 1
            this.modalError = null
            this.modalOpen = false
        },
        async order() {
            this.loading = true
            this.modalError = null

            try {
                await api.post('stonks/order', {
                    stock_id: this.stonk.id,
                    amount: this.amount,
                    order_mode: this.orderMode,
                })
                this.orderSuccess = true

                await this.$store.dispatch('getUser')
                await this.$store.dispatch('getStonks')
            } catch (error) {
                console.log(error)
                this.modalError = error.response.data.error
            } finally {
                this.loading = false
            }
        }
    },
}

</script>
