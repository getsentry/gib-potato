<template>

    <div class="flex items-center">
        <h2 class="text-lg font-medium leading-6">
            Your current balance is {{ user.spendable_count ?? 0 }} 🥔
        </h2>
        <span class="mx-4">-</span>
        <span>Out of potato?</span>
        <a
            href="/gib-credit"
            class="ml-4 flex justify-center rounded-md border border-zinc-300 px-3 py-1 text-sm"
        >
            Gib Credit
        </a>
    </div>

    <div v-if="stocks.market_open === false">
        <div class="absolute inset-0 flex items-center justify-center">
            <h1 class="text-2xl font-extrabold">
                We are currently not trading...
            </h1>
        </div>
    </div>

    <div v-else>
        <div class="mt-16 grid grid-cols-1 gap-y-12 sm:grid-cols-2 sm:gap-x-6 lg:grid-cols-3 xl:gap-x-8">
            <div
                v-for="(stock, index) in stocks.stocks"
                class="h-full flex flex-col"
            >
                <div
                    :index="index"
                >
                    <div>
                        <Line :data="stock.data" :options="chartOptions" />
                    </div>
                    <div class="mt-4 flex items-center">
                        <div>
                            <h3 class="text-sm font-medium">{{ stock.symbol }}</h3>
                            <p class="mt-1 text-sm text-zinc-500">
                                {{ stock.description }}
                            </p>
                        </div>
                        <span class="ml-auto">
                            <span class="text-xl font-semibold">
                                {{ stock.share_price }}
                            </span>
                            <template v-if="stock.stock_info.amount > 0">
                                <span class="ml-4 text-green-500">
                                    +{{ stock.stock_info.amount }}
                                </span>
                            </template>
                            <template v-if="stock.stock_info.amount < 0">
                                <span class="ml-4 text-red-500">
                                    {{ stock.stock_info.amount }}
                                </span>
                            </template>
                            <template v-if="stock.stock_info.amount === 0">
                                <span class="ml-4 text-zinc-500">
                                    {{ stock.stock_info.amount }}
                                </span>
                            </template>
                        </span>
                    </div>
                    <div class="mt-4 flex">
                        <div class="flex-1">
                            <div class="flex text-sm">
                                <span class="text-zinc-500">Open</span>
                                <span class="ml-auto font-semibold">{{ stock.stock_info.open }}</span>
                            </div>
                            <div class="flex text-sm">
                                <span class="text-zinc-500">High</span>
                                <span class="ml-auto font-semibold">{{ stock.stock_info.high }}</span>
                            </div>
                            <div class="flex text-sm">
                                <span class="text-zinc-500">Low</span>
                                <span class="ml-auto font-semibold">{{ stock.stock_info.low }}</span>
                            </div>
                        </div>
                        <hr class="mx-2">
                        <div class="flex-1">
                            <div class="flex text-sm">
                                <span class="text-zinc-500">Vol</span>
                                <span class="ml-auto font-semibold">{{ stock.stock_info.volume }}</span>
                            </div>
                            <div class="flex text-sm">
                                <span class="text-zinc-500">Mkt Cap</span>
                                <span class="ml-auto font-semibold">{{ stock.stock_info.market_cap }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="mt-8">
                        <button
                            class="inline-flex w-full justify-center rounded-md border border-transparent bg-amber-200 text-zinc-900 px-4 py-2 text-md"
                            @click="openModal(stock, 'buy')"
                        >
                            Buy
                        </button>
                        <button
                            class="mt-2 inline-flex w-full justify-center rounded-md border border-zinc-300 px-4 py-2 text-md"
                            @click="openModal(stock, 'sell')"
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
                v-if="stocks.portfilio.length"
                v-for="stock in stocks.portfilio"
                class="flex-1 flex items-center"
            >
                <div>
                    <h3 class="flex items-center font-medium">
                        {{ stock.symbol }}
                        <small class="ml-1.5">×{{ stock.count }}</small>
                    </h3>
                    <div>
                        current value <span class="font-semibold">{{ stock.value }}</span> 🥔
                    </div>
                </div>
            </div>
            <div v-else>
                <span class="text-zinc-500">No stocks yet</span>
            </div>
        </div>

        <h2 class="mt-16 text-lg font-medium leading-6">
            Your order history
        </h2>
        <table
            v-if="stocks.trades.length"
            class="mt-4 mb-36 w-full table-auto divide-y divide-zinc-300"
        >
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
                        Max/Min Price
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
                <tr v-for="trade in stocks.trades" :key="trade.id">
                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-bold">
                        {{ trade.symbol }}
                    </td>
                    <td class="whitespace-nowrap py-4 px-3 text-sm">
                        <span
                            v-if="trade.type === 'buy'"
                            class="text-sm font-medium ml-auto px-2.5 py-0.5 rounded bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300"
                        >
                            {{ trade.type }} order
                        </span>
                        <span
                            v-if="trade.type === 'sell'"
                            class="text-sm font-medium ml-auto px-2.5 py-0.5 rounded bg-teal-100 text-teal-800 dark:bg-teal-900 dark:text-teal-300"
                        >
                            {{ trade.type }} order
                        </span>
                    </td>
                    <td class="whitespace-nowrap py-4 px-3 text-sm">
                        <span
                            class="text-sm font-medium ml-auto px-2.5 py-0.5 rounded"
                            :class="{
                                '!bg-green-100 !text-green-800 dark:!bg-green-900 dark:!text-green-300': trade.status === 'done',
                                '!bg-rose-100 !text-rose-800 dark:!bg-rose-900 dark:!text-rose-300': trade.status === 'expired',
                                '!bg-red-100 !text-red-800 dark:!bg-red-900 dark:!text-red-300': trade.status === 'canceled',
                                '!bg-blue-100 !text-blue-800 dark:!bg-blue-900 dark:!text-blue-300': trade.status === 'pending',
                            }"
                        >
                            {{ trade.status }}
                        </span>
                    </td>
                    <td class="whitespace-nowrap py-4 px-3 text-right text-sm font-bold">
                        <template v-if="trade.type === 'sell' && trade.proposed_price">
                            <span class="text-green-500">
                                +{{ trade.proposed_price }}
                            </span>
                        </template>
                        <template v-if="trade.type === 'buy' && trade.proposed_price">
                            <span class="text-red-500">
                                -{{ trade.proposed_price }}
                            </span>
                        </template>
                    </td>
                    <td class="whitespace-nowrap py-4 px-3 text-right text-sm font-bold">
                        <template v-if="trade.type === 'sell' && trade.price">
                            <span class="text-green-500">
                                +{{ trade.price }}
                            </span>
                        </template>
                        <template v-if="trade.type === 'buy' && trade.price">
                            <span class="text-red-500">
                                -{{ trade.price }}
                            </span>
                        </template>
                        <template v-if="(trade.type === 'sell' || trade.type === 'buy') && !trade.price">
                            <span class="text-zinc-500">-</span>
                        </template>
                    </td>
                    <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm">
                        {{ trade.time }}
                    </td>
                </tr>
            </tbody>
        </table>
        <div
            v-else
            class="mt-4 mb-36"
        >
            <span class="text-zinc-500">No orders yet</span>
        </div>
        <div v-if="modalOpen === true" class="relative z-10">
            <div class="fixed inset-0 bg-zinc-700 bg-opacity-75"></div>

            <div class="fixed inset-0 z-10 overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    <div class="relative transform overflow-hidden rounded-lg bg-zinc-50 dark:bg-zinc-900 px-4 pt-5 pb-4 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-md sm:p-6">
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
                                    <Line :data="stock.data" :options="chartOptions" />
                                </div>
                                <div class="mt-4 flex items-center">
                                    <div>
                                        <h3 class="text-sm font-medium">{{ stock.symbol }}</h3>
                                        <p class="mt-1 text-sm text-zinc-500">
                                            {{ stock.description }}
                                        </p>
                                    </div>
                                    <span class="ml-auto">
                                        <span class="text-xl font-semibold">
                                            {{ stock.share_price }}
                                        </span>
                                        <template v-if="stock.stock_info.amount > 0">
                                            <span class="ml-4 text-green-500">
                                                +{{ stock.stock_info.amount }}
                                            </span>
                                        </template>
                                        <template v-if="stock.stock_info.amount < 0">
                                            <span class="ml-4 text-red-500">
                                                {{ stock.stock_info.amount }}
                                            </span>
                                        </template>
                                        <template v-if="stock.stock_info.amount === 0">
                                            <span class="ml-4 text-zinc-500">
                                                {{ stock.stock_info.amount }}
                                            </span>
                                        </template>
                                    </span>
                                </div>
                                <div class="mt-4 flex">
                                    <div class="flex-1">
                                        <div class="flex text-sm">
                                            <span class="text-zinc-500">Open</span>
                                            <span class="ml-auto font-semibold">{{ stock.stock_info.open }}</span>
                                        </div>
                                        <div class="flex text-sm">
                                            <span class="text-zinc-500">High</span>
                                            <span class="ml-auto font-semibold">{{ stock.stock_info.high }}</span>
                                        </div>
                                        <div class="flex text-sm">
                                            <span class="text-zinc-500">Low</span>
                                            <span class="ml-auto font-semibold">{{ stock.stock_info.low }}</span>
                                        </div>
                                    </div>
                                    <hr class="mx-2">
                                    <div class="flex-1">
                                        <div class="flex text-sm">
                                            <span class="text-zinc-500">Vol</span>
                                            <span class="ml-auto font-semibold">{{ stock.stock_info.volume }}</span>
                                        </div>
                                        <div class="flex text-sm">
                                            <span class="text-zinc-500">Mkt Cap</span>
                                            <span class="ml-auto font-semibold">{{ stock.stock_info.market_cap }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div
                                    v-if="orderSuccess === false"
                                    class="mt-8"
                                >
                                    <label class="block text-sm">
                                        <template v-if="orderMode === 'buy'">
                                            Maximum buy price per share
                                        </template>
                                        <template v-if="orderMode === 'sell'">
                                            Minimum sell price per share
                                        </template>
                                    </label>
                                    <input
                                        type="number"
                                        min="1"
                                        max="9999"
                                        v-model="price"
                                        class="mb-3 w-full rounded-md text-sm p-2 text-zinc-900 border border-zinc-300 ring-offset-zinc-50 dark:ring-offset-zinc-900 focus:border-indigo-500 focus:ring-indigo-500"
                                        placeholder="123"
                                    />
                                    <label class="block text-sm">
                                        Amount
                                    </label>
                                    <input
                                        type="number"
                                        min="1"
                                        max="100"
                                        v-model="amount"
                                        class="mb-3 w-full rounded-md text-sm p-2 text-zinc-900 border border-zinc-300 ring-offset-zinc-50 dark:ring-offset-zinc-900 focus:border-indigo-500 focus:ring-indigo-500"
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
                                    v-if="user.spendable_count >= price * amount"
                                    class="inline-flex w-full justify-center rounded-md border border-transparent bg-amber-200 text-zinc-900 px-4 py-2 text-base font-medium sm:col-start-2 sm:text-sm"
                                    :disabled="price === null || amount === null || price <= 0 || amount <= 0"
                                    :class="{ 'opacity-50': price === null || amount === null || price <= 0 || amount <= 0 }"
                                    @click="order()"
                                >
                                    Buy for {{ price && amount ? price * amount : '' }} <span class="ml-2" :class="{ 'animate-spin': loading }">🥔</span>
                                </button>
                                <button
                                    v-else
                                    class="opacity-50 inline-flex w-full justify-center rounded-md border border-transparent bg-amber-200 text-zinc-900 px-4 py-2 text-base font-medium sm:col-start-2 sm:text-sm"
                                    disabled
                                >
                                    Not enough 🥔
                                </button>
                            </template>
                            <template v-if="orderMode === 'sell'">
                                <button
                                    v-if="stocks.portfilio.length && stocks.portfilio.find(stck => stck.symbol === stock.symbol).count >= amount"
                                    class="inline-flex w-full justify-center rounded-md border border-transparent bg-amber-200 text-zinc-900 px-4 py-2 text-base font-medium sm:col-start-2 sm:text-sm"
                                    :disabled="price === null || amount === null || price <= 0 || amount <= 0"
                                    :class="{ 'opacity-50': price === null || amount === null || price <= 0 || amount <= 0 }"
                                    @click="order()"
                                >
                                    Sell for {{ price * amount }} <span class="ml-2" :class="{ 'animate-spin': loading }">🥔</span>
                                </button>
                                <button
                                    v-else
                                    class="opacity-50 inline-flex w-full justify-center rounded-md border border-transparent bg-amber-200 text-zinc-900 px-4 py-2 text-base font-medium sm:col-start-2 sm:text-sm"
                                    disabled
                                >
                                    Not enough {{ stock.symbol }}
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
                            <div class="mb-4 text-center">
                                Your order was submitted successfully ✅
                            </div>
                            <button
                                class="inline-flex w-full justify-center rounded-md border border-zinc-300 bg-zinc-100 px-4 py-2 text-base font-medium text-zinc-900 sm:mt-0 sm:text-sm"
                                :disabled="loading"
                                @click="closeModal"
                            >
                                Close
                            </button>
                        </div>
                        <div class="mt-5 text-xs text-center text-zinc-500">
                            <a href="/terms" target="_blank" class="underline">Terms & Conditions</a> apply.
                        </div>
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
    name: 'Stocks',
    components: {
        Line,
    },
    setup() {
        const store = useStore()

        return {
            user: computed(() => store.getters.user),
            stocks: computed(() => store.getters.stocks),
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
                            color: function() {
                                if (
                                    window.matchMedia &&
                                    window.matchMedia('(prefers-color-scheme: dark)').matches
                                ) {
                                    return '#e4e4e7'
                                }

                                return '#27272a'
                            },
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
                            color: function() {
                                if (
                                    window.matchMedia &&
                                    window.matchMedia('(prefers-color-scheme: dark)').matches
                                ) {
                                    return '#e4e4e7'
                                }

                                return '#27272a'
                            },
                            precision: 0,
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
            stock: null,
            orderMode: 'buy',
            orderSuccess: false,
            amount: 1,
            price: null,
            modalOpen: false,
            modalError: null,
            loading: false,
        }
    },
    mounted() {
        window.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeModal()
            }
        })
    },
    beforeUnmount() {
        window.removeEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeModal()
            }
        })
    },
    methods: {
        openModal(stock, orderMode) {
            this.stock = stock
            this.orderMode = orderMode
            this.modalOpen = true
        },
        closeModal() {
            this.stock = null
            this.orderMode = 'buy'
            this.orderSuccess = false
            this.amount = 1
            this.price = null
            this.modalError = null
            this.modalOpen = false
        },
        async order() {
            this.loading = true
            this.modalError = null

            try {
                await api.post('stocks/order', {
                    stock_id: this.stock.id,
                    amount: this.amount,
                    proposed_price: this.price,
                    order_mode: this.orderMode,
                })
                this.orderSuccess = true

                await this.$store.dispatch('getUser')
                await this.$store.dispatch('getStocks')
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
