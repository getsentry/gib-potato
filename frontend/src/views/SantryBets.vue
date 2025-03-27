<template>

    <div class="flex items-center">
        <h2 class="text-lg font-medium leading-6">
            Your current balance is {{ user.spendable_count ?? 0 }} 🥔
        </h2>
        <span class="mx-4">-</span>
        <span>Out of potato?</span>
        <button class="ml-4 flex justify-center rounded-md border border-zinc-300 px-3 py-1 text-sm">Gib Credit</button>
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
    name: 'SantryBets',
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
            price: null,
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
            this.price = null
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
                    proposed_price: this.price,
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
