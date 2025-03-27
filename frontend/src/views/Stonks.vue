<template>

    <div class="flex items-center">
        <h2 class="text-lg font-medium leading-6">
            You can spend up to {{ user.spendable_count ?? 0 }} 🥔
        </h2>
        <span class="mx-4">-</span>
        <span>Out of potato?</span>
        <button class="ml-4 flex justify-center rounded-md border border-zinc-300 px-3 py-1 text-sm">Gib Credit</button>
    </div>

    <div class="mt-16 grid grid-cols-1 gap-y-12 sm:grid-cols-2 sm:gap-x-6 lg:grid-cols-4 xl:gap-x-8 mb-32">
        <div
            v-for="(stonk, index) in stonks"
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
                                'text-green-500': stonk.stock_info.amount > 0,
                                'text-red-500': stonk.stock_info.amount < 0,
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
                    </div>
                </div>
                <div class="mt-8">
                    <button class="inline-flex w-full justify-center rounded-md border border-transparent bg-amber-200 text-zinc-900 px-4 py-2 text-md">Buy</button>
                    <button class="mt-2 inline-flex w-full justify-center rounded-md border border-zinc-300 px-4 py-2 text-md">Sell</button>
                </div>
            </div>
        </div>
    </div>

</template>

<script>
import { computed } from 'vue'
import { useStore } from 'vuex'

import { Line } from 'vue-chartjs'
import { Chart as ChartJS, CategoryScale, LinearScale, PointElement, LineElement, Legend } from 'chart.js'

ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Legend
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
                        borderColor: 'white',
                        borderWidth: 1,
                        tension: 0.1,
                    }
                },
                // scales: {
                //     y: {
                //         min: 0,
                //         max: 150
                //     }
                // }
            },
        }
    },
}

</script>
