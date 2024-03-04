<template>
    <div class="bg-gray-700 mb-8 mx-20 border-t border-b text-white px-4 py-3" role="alert">
        <p class="font-bold">How to?</p>
        <p class="text-sm">Write a message in Slack - if it contains <span class="bg-blue-700 p-0.5 px-1 rounded-lg">#quickwin</span> and a 🥔 emoji it will be captured here.</p>
    </div>
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
    <div v-if="modalOpen === true" class="relative z-10">
        <div class="fixed inset-0 bg-zinc-700 bg-opacity-75"></div>

        <div class="fixed inset-0 z-20 overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-lg bg-zinc-50 dark:bg-zinc-900 px-4 pt-5 pb-4 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-sm sm:p-6">
                    <div class="relative">
                        <div class="relative mt-4">
                            <div class="flex items-center space-x-2 rtl:space-x-reverse">
                                <span class="text-sm font-semibold text-gray-900 dark:text-white">{{modelData.user.slack_name}}</span>
                                <span class="text-sm font-normal text-gray-500 dark:text-gray-400">{{ new Date(modelData.created).toLocaleDateString('en-us', { year:"numeric", month:"short", day:"numeric"}) }}</span>
                            </div>
                            <FormattedMessage :message="modelData.message" />
                            <button
                                class="mt-2 cursor-pointer inline-flex w-full justify-center rounded-md border border-zinc-300 bg-zinc-100 px-4 py-2 text-base font-medium text-zinc-900 sm:mt-0 sm:text-sm"
                                @click="closeModal"
                            >
                            Close
                            </button>
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
import FormattedMessage from '../components/FormattedMessage.vue'
import JSConfetti from 'js-confetti'

export default {
    name: 'Quickwins',
    components: {
        FormattedMessage,
    },
    created() {
        let that = this;

        document.addEventListener('keyup', function (evt) {
            if (evt.keyCode === 27) {
                that.closeModal();
            }
        });
        if (this.modalOpen === true) {
            const jsConfetti = new JSConfetti()
            const startTime = Date.now();
            let intervalId = setInterval(() => {
                jsConfetti.addConfetti({
                    emojis: ['🥔'],
                });
                if (Date.now() - startTime > 3000) {
                    clearInterval(intervalId);
                }
            }, 300)
        }
        
    },
    data() {
        let modelData = null
        let modalOpen = false
        
        this.taggedMessages.forEach((message) => {
            if (message.id === this.$route.query?.id) {
                modelData = message
                modalOpen = true
            }
        });

        return {
            modalOpen,
            modelData, 
        }
    },
    methods: {
        openModal() {
            this.modalOpen = true
        },
        closeModal() {
            this.modalOpen = false
            this.$router.replace({'query': null})
        },
    },

    setup() {
        const store = useStore()

        const taggedMessages = computed(() => store.getters.taggedMessages);
        return {
            taggedMessages,
        }
    },
}

</script>
